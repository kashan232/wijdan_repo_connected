<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\PurchaseItem;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use App\Models\VendorLedger;
use App\Models\InwardGatepass;
use App\Models\PurchaseReturn;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchaseQuery = Purchase::with([
            'branch',
            'warehouse',
            'vendor',
            'items.product',
            'return'
        ]);

        if ($request->start_date && $request->end_date) {
            $purchaseQuery->whereBetween('purchase_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $purchases = $purchaseQuery->get();
        $inwardQuery = InwardGatepass::with([
            'branch',
            'warehouse',
            'vendor',
            'items.product'
        ])
            ->where('status', 'linked')
            ->where('bill_status', 'billed');

        if ($request->start_date && $request->end_date) {
            $inwardQuery->whereBetween('gatepass_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $inwards = $inwardQuery->get();

        /* ================= MERGE ================= */
        $Purchase = $purchases->concat($inwards);

        $Purchase = $Purchase->sortByDesc(function ($row) {
            return \Carbon\Carbon::parse(
                $row instanceof \App\Models\Purchase
                    ? $row->purchase_date
                    : $row->gatepass_date
            )->timestamp . ($row->created_at->timestamp ?? 0);
        })->values(); // 👈 reset keys

        return view('admin_panel.purchase.index', compact('Purchase'))
            ->with([
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
            ]);
    }


    public function addBill($gatepassId)
    {
        // Fetch the gatepass along with its related items and products
        $gatepass = InwardGatepass::with('items.product')->findOrFail($gatepassId);
        return view('admin_panel.inward.add_bill', compact('gatepass'));
    }

    public function editBill($gatepassId)
    {
        // Fetch the gatepass along with its related items and products for editing
        $gatepass = InwardGatepass::with('items.product', 'warehouse', 'vendor')->findOrFail($gatepassId);
        return view('admin_panel.inward.edit_bill', compact('gatepass'));
    }


    public function add_purchase()
    {
        // $userId = Auth::id();
        $Purchase = Purchase::get();
        $Vendor = Vendor::get();
        $Warehouse = Warehouse::get();
        return view('admin_panel.purchase.add_purchase', compact('Vendor', "Warehouse", 'Purchase'));
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'vendor_id'       => 'nullable|exists:vendors,id',
                'purchase_date'   => 'nullable|date',
                'purchase_to'     => 'required|in:shop,warehouse',
                'warehouse_id'    => 'nullable|required_if:purchase_to,warehouse|exists:warehouses,id',

                'note'            => 'nullable|string',
                'job_description' => 'nullable|string',
                'discount'        => 'nullable|numeric|min:0',
                'extra_cost'      => 'nullable|numeric|min:0',

                'product_id'      => 'array',
                'product_id.*'    => 'nullable|exists:products,id',

                'qty'             => 'array',
                'qty.*'           => 'nullable|required_with:product_id.*|numeric|min:1',

                'price'           => 'array',
                'price.*'         => 'nullable|required_with:product_id.*|numeric|min:0',

                'unit'            => 'array',
                'unit.*'          => 'nullable|required_with:product_id.*|string',

                'item_discount'   => 'nullable|array',
                'item_discount.*' => 'nullable|numeric|min:0',

                'item_disc'       => 'nullable|array',
                'item_disc.*'     => 'nullable|numeric|min:0',

                'item_note'   => 'nullable|array',
                'item_note.*' => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated, $request) {

                /* ================= PURCHASE MASTER ================= */

                $invoiceNo = Purchase::generateInvoiceNo();

                $purchase = Purchase::create([
                    'branch_id'     => auth()->id(),
                    'vendor_id'     => $validated['vendor_id'] ?? null,
                    'purchase_date' => $validated['purchase_date'] ?? now(),
                    'invoice_no'    => $invoiceNo,
                    'company_invoice_no' => $request->company_invoice_no ?? $request->purchase_order_no,

                    'purchase_to'   => $validated['purchase_to'], // 🔥 shop | warehouse
                    'warehouse_id'  => $validated['purchase_to'] === 'warehouse'
                        ? $validated['warehouse_id']
                        : null,

                    'note'          => $validated['note'] ?? null,
                    'job_description' => $validated['job_description'] ?? null,

                    'subtotal'      => 0,
                    'discount'      => 0,
                    'extra_cost'    => 0,
                    'net_amount'    => 0,
                    'paid_amount'   => 0,
                    'due_amount'    => 0,
                ]);

                $subtotal = 0;

                // discount source (new or old)
                $itemDiscounts = $validated['item_discount'] ?? $request->input('item_disc', []);

                /* ================= ITEMS LOOP ================= */

                foreach (($validated['product_id'] ?? []) as $index => $productId) {

                    $qty   = $validated['qty'][$index] ?? 0;
                    $price = $validated['price'][$index] ?? 0;

                    if (!$productId || !$qty || !is_numeric($price)) {
                        continue;
                    }

                    $discPerPiece = isset($itemDiscounts[$index])
                        ? floatval($itemDiscounts[$index])
                        : 0;

                    $unitPriceAfterDisc = max(0, $price - $discPerPiece);
                    $lineTotal = $unitPriceAfterDisc * $qty;

                    PurchaseItem::create([
                        'purchase_id'   => $purchase->id,
                        'product_id'    => $productId,
                        'unit'          => $validated['unit'][$index] ?? null,
                        'price'         => $price,
                        'item_discount' => $discPerPiece,
                        'qty'           => $qty,
                        'note'          => $request->item_note[$index] ?? null, // ✅ NOTE
                        'line_total'    => $lineTotal,
                    ]);


                    $subtotal += $lineTotal;

                    /* ================= STOCK UPDATE ================= */

                    if ($validated['purchase_to'] === 'shop') {

                        // ➕ SHOP STOCK
                        $stock = Stock::where('branch_id', auth()->id())
                            ->where('product_id', $productId)
                            ->first();

                        if ($stock) {
                            $stock->qty += $qty;
                            $stock->save();
                        } else {
                            Stock::create([
                                'branch_id'  => auth()->id(),
                                'product_id' => $productId,
                                'qty'        => $qty,
                            ]);
                        }
                    } else {

                        // ➕ WAREHOUSE STOCK
                        $warehouseId = $validated['warehouse_id'];

                        $warehouseStock = WarehouseStock::where('warehouse_id', $warehouseId)
                            ->where('product_id', $productId)
                            ->first();

                        if ($warehouseStock) {
                            $warehouseStock->quantity += $qty;
                            $warehouseStock->save();
                        } else {
                            WarehouseStock::create([
                                'warehouse_id' => $warehouseId,
                                'product_id'   => $productId,
                                'quantity'     => $qty,
                                'remarks'      => 'Purchase Entry',
                            ]);
                        }
                    }
                }

                /* ================= TOTALS ================= */

                $discount  = $request->discount ?? 0;
                $extraCost = $request->extra_cost ?? 0;
                $netAmount = ($subtotal - $discount) + $extraCost;

                $purchase->update([
                    'subtotal'   => $subtotal,
                    'discount'   => $discount,
                    'extra_cost' => $extraCost,
                    'net_amount' => $netAmount,
                    'due_amount' => $netAmount,
                ]);

                /* ================= VENDOR LEDGER ================= */

                /* ================= VENDOR LEDGER ================= */
                
                $ledger = VendorLedger::firstOrNew(['vendor_id' => $validated['vendor_id'] ?? null]);
                
                // If new ledger (shouldn't happen if vendor created properly, but safety fallback)
                if (!$ledger->exists) {
                    // Initialize with Vendor's opening balance if available
                    $v = Vendor::find($validated['vendor_id']);
                    $initialParams = $v ? $v->opening_balance : 0;
                    $ledger->opening_balance = $initialParams;
                    $ledger->closing_balance = $initialParams;
                    $ledger->previous_balance = 0;
                }

                // Update stats
                $ledger->admin_or_user_id = auth()->id();
                $ledger->previous_balance = $ledger->closing_balance; // Set previous to what it was before this purchase
                $ledger->closing_balance  += $netAmount; // Add purchase amount
                $ledger->save();
            });

            return redirect()
                ->route('Purchase.home')
                ->with('success', 'Purchase has been successfully added');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error occurred while saving: ' . $e->getMessage());
        }
    }


    public function store_inwardbill(Request $request, $gatepassId)
    {
        try {
            $validated = $request->validate([
                'vendor_id'       => 'required|exists:vendors,id',
                'received_in'     => 'required|in:shop,warehouse',
                'warehouse_id'    => 'nullable|required_if:received_in,warehouse|exists:warehouses,id',
                'purchase_date'   => 'required|date',
                'company_invoice_no' => 'required|string|max:255',
                'discount'        => 'nullable|numeric|min:0',
                'extra_cost'      => 'nullable|numeric|min:0',
                'subtotal'        => 'nullable|numeric|min:0',
                'net_amount'      => 'nullable|numeric|min:0',

                'product_id'      => 'array',
                'product_id.*'    => 'nullable|exists:products,id',
                'qty'             => 'array',
                'qty.*'           => 'nullable|numeric|min:1',
                'price'           => 'array',
                'price.*'         => 'required|numeric|min:0',

                // ✅ ADD THESE
                'item_discount'           => 'nullable|array',
                'item_discount.*'         => 'nullable|numeric|min:0',
                'discount_type'           => 'nullable|array',
                'discount_type.*'         => 'nullable|in:pkr,percent',
            ]);


            DB::transaction(function () use ($validated, $gatepassId, $request) {

                // 🔹 Get existing gatepass
                $gatepass = InwardGatepass::findOrFail($gatepassId);

                // 🔹 Use the same invoice_no (no new number)
                $invoiceNo = $gatepass->invoice_no;

                // 🔹 Take totals directly from request
                $subtotal   = $request->subtotal ?? 0;
                $discount   = $request->discount ?? 0;
                $extraCost  = $request->extra_cost ?? 0;
                $netAmount  = $request->net_amount ?? 0;

                // 🔹 Update Gatepass record
                $gatepass->update([
                    'receive_type'   => $validated['received_in'],
                    'gatepass_date'  => $validated['purchase_date'], // ✅ Update with Bill Date
                    'warehouse_id'  => $validated['received_in'] === 'warehouse'
                        ? $validated['warehouse_id']
                        : null,
                    'company_invoice_no'  => $validated['company_invoice_no'], // ✅ NEW
                    'subtotal'      => $subtotal,
                    'discount'      => $discount,
                    'extra_cost'    => $extraCost,
                    'net_amount'    => $netAmount,
                    'item_discount' => collect($request->item_discount)->sum() ?? 0,
                    'paid_amount'   => 0,
                    'due_amount'    => $netAmount,
                    'bill_status'   => 'billed',
                    'status'        => 'linked',
                ]);

                foreach ($gatepass->items as $item) {
                    $discountValue = $request->item_discount[$item->id] ?? 0;
                    $discountType  = $request->discount_type[$item->id] ?? 'pkr';
                    $price         = $request->price[$item->id] ?? 0; // ✅ Catch price

                    $item->price          = $price; // ✅ Save Price
                    $item->discount_value = $discountValue;
                    $item->discount_type  = $discountType;
                    $item->save();
                }
                $ledger = VendorLedger::firstOrNew(['vendor_id' => $validated['vendor_id']]);

                if (!$ledger->exists) {
                    // Initialize if new
                    $v = Vendor::find($validated['vendor_id']);
                    $initialParams = $v ? $v->opening_balance : 0;
                    $ledger->opening_balance = $initialParams;
                    $ledger->closing_balance = $initialParams;
                    $ledger->previous_balance = 0;
                }

                $ledger->admin_or_user_id = auth()->id();
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance  += (float)$netAmount;
                $ledger->save();
            });

            return redirect()->route('InwardGatepass.home')->with('success', 'Bill successfully added to the Inward Gatepass.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error occurred: ' . $e->getMessage());
        }
    }

    public function update_inwardbill(Request $request, $gatepassId)
    {
        $validated = $request->validate([
            'vendor_id'       => 'required|exists:vendors,id',
            'received_in'     => 'required|in:shop,warehouse',
            'warehouse_id'    => 'nullable|required_if:received_in,warehouse|exists:warehouses,id',
            'purchase_date'   => 'required|date',
            'company_invoice_no' => 'required|string|max:255',
            'discount'        => 'nullable|numeric|min:0',
            'extra_cost'      => 'nullable|numeric|min:0',
            'subtotal'        => 'nullable|numeric|min:0',
            'net_amount'      => 'nullable|numeric|min:0',

            'product_id'      => 'array',
            'product_id.*'    => 'nullable|exists:products,id',
            'qty'             => 'array',
            'qty.*'           => 'nullable|numeric|min:1',
            'price'           => 'array',
            'price.*'         => 'required|numeric|min:0',

            // ✅ ADD THESE
            'item_discount'           => 'nullable|array',
            'item_discount.*'         => 'nullable|numeric|min:0',
            'discount_type'           => 'nullable|array',
            'discount_type.*'         => 'nullable|in:pkr,percent',
        ]);


        DB::transaction(function () use ($validated, $gatepassId, $request) {

            // 🔹 Get existing gatepass
            $gatepass = InwardGatepass::findOrFail($gatepassId);

            // 🔹 Store old net_amount for ledger adjustment
            $oldNetAmount = $gatepass->net_amount ?? 0;

            // 🔹 Take totals directly from request
            $subtotal   = $request->subtotal ?? 0;
            $discount   = $request->discount ?? 0;
            $extraCost  = $request->extra_cost ?? 0;
            $netAmount  = $request->net_amount ?? 0;

            // 🔹 Update Gatepass record
            $gatepass->update([
                'receive_type'   => $validated['received_in'],
                'gatepass_date'  => $validated['purchase_date'], // ✅ Update with Bill Date
                'warehouse_id'  => $validated['received_in'] === 'warehouse'
                    ? $validated['warehouse_id']
                    : null,
                'company_invoice_no'  => $validated['company_invoice_no'], // ✅ NEW
                'subtotal'      => $subtotal,
                'discount'      => $discount,
                'extra_cost'    => $extraCost,
                'net_amount'    => $netAmount,
                'item_discount' => collect($request->item_discount)->sum() ?? 0,
                'paid_amount'   => $gatepass->paid_amount ?? 0,  // Keep existing paid amount
                'due_amount'    => $netAmount - ($gatepass->paid_amount ?? 0),  // Recalculate due
                'bill_status'   => 'billed',
                'status'        => 'linked',
            ]);


            foreach ($gatepass->items as $item) {
                $discountValue = $request->item_discount[$item->id] ?? 0;
                $discountType  = $request->discount_type[$item->id] ?? 'pkr';
                $price         = $request->price[$item->id] ?? 0; // ✅ Catch price
                
                $item->price          = $price; // ✅ Save Price
                $item->discount_value = $discountValue;
                $item->discount_type  = $discountType;
                $item->save();
            }

            // 🔹 Adjust Vendor Ledger
            $ledger = VendorLedger::firstOrNew(['vendor_id' => $validated['vendor_id']]);
            
            if (!$ledger->exists) {
                // Initialize if new (Edge case)
                $v = Vendor::find($validated['vendor_id']);
                $initialParams = $v ? $v->opening_balance : 0;
                $ledger->opening_balance = $initialParams;
                $ledger->closing_balance = $initialParams;
                $ledger->previous_balance = 0;
                // If creating new, the "old net amount" didn't exist in this ledger, so we just add the NEW net amount?
                // Actually if ledger didn't exist, we assume closing balance starts at opening.
                // The difference logic relies on ledger having the OLD bill amount.
                // If ledger is NEW, it means it missed the old bill. So we should probably just add the NEW Net Amount effectively.
                // amountDifference = netAmount - oldNetAmount.
                // If ledger didn't exist, oldNetAmount wasn't in it. So we should add netAmount.
                // But let's stick to standard difference logic assuming consistency.
                // If ledger missing, we assume it's fresh.
                // effectively closing = opening + netAmount.
            }
            
            // If ledger existed, we apply difference. 
            // If it's new, we apply difference? 
            // oldNetAmount is from GATEPASS. Ledger is unrelated entity.
            // If ledger is new, closing = opening.
            // We want closing = opening + netAmount.
            // difference = netAmount - oldNetAmount.
            // If we do closing + difference => opening + netAmount - oldNetAmount.
            // This subtracts oldNetAmount which wasn't there!
            // So if ledger is NEW, we should NOT subtract oldNetAmount.
            
            if ($ledger->wasRecentlyCreated || !$ledger->exists) {
                // New ledger: Closing = Opening + New Bill Amount
                $ledger->closing_balance += $netAmount;
            } else {
                // Existing ledger: Apply difference
                $amountDifference = $netAmount - $oldNetAmount;
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance += $amountDifference;
            }

            $ledger->admin_or_user_id = auth()->id();
            $ledger->save();
        });


        return redirect()->route('InwardGatepass.home')->with('success', 'Bill successfully updated.');
    }




    // public function store_inwardbill(Request $request, $gatepassId = null)
    // {
    //     dd($request);
    //     if ($gatepassId) {
    //         $gatepass = InwardGatepass::with('purchase')->findOrFail($gatepassId);
    //         if ($gatepass->purchase) {
    //             return back()->with('error', 'This gatepass already has an associated bill.');
    //         }
    //     } else {
    //         $gatepass = null;
    //     }
    //     $validated = $request->validate([
    //         'invoice_no'      => 'nullable|string',
    //         'vendor_id'       => 'nullable|exists:vendors,id',
    //         'purchase_date'   => 'nullable|date',
    //         'warehouse_id'    => 'nullable|exists:warehouses,id',
    //         'note'            => 'nullable|string',
    //         'discount'        => 'nullable|numeric|min:0',
    //         'extra_cost'      => 'nullable|numeric|min:0',
    //         'product_id'      => 'array',
    //         'product_id.*'    => 'nullable|exists:products,id',
    //         'qty'             => 'array',
    //         'qty.*'           => 'nullable|required_with:product_id.*|numeric|min:1',
    //         'price'           => 'array',
    //         'price.*'         => 'nullable|required_with:product_id.*|numeric|min:0',
    //         'unit'            => 'array',
    //         'unit.*'          => 'nullable|required_with:product_id.*|string',
    //         'item_discount'   => 'nullable|array',
    //         'item_discount.*' => 'nullable|numeric|min:0',
    //     ]);
    //     DB::transaction(function () use ($validated, $request, $gatepass) {
    //         $lastInvoice = Purchase::latest()->value('invoice_no');
    //         $nextInvoice = $lastInvoice
    //             ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    //             : 'INV-00001';
    //         $purchase = Purchase::create([
    //             'branch_id'       => auth()->user()->id,
    //             'warehouse_id'    => $validated['warehouse_id'],
    //             'vendor_id'       => $validated['vendor_id'],
    //             'purchase_date'   => $validated['purchase_date'] ?? now(),
    //             'invoice_no'      => $validated['invoice_no'] ?? $nextInvoice,
    //             'note'             => $validated['note'] ?? null,
    //             'subtotal'        => 0,
    //             'discount'        => 0,
    //             'extra_cost'      => 0,
    //             'net_amount'      => 0,
    //             'paid_amount'     => 0,
    //             'due_amount'      => 0,
    //         ]);
    //         $subtotal = 0;
    //         foreach ($validated['product_id'] as $index => $productId) {
    //             $qty = $validated['qty'][$index] ?? 0;
    //             $price = $validated['price'][$index] ?? 0;
    //             if (empty($productId) || empty($qty) || empty($price)) {
    //                 continue;
    //             }
    //             $disc = $validated['item_discount'][$index] ?? 0;
    //             $unit = $validated['unit'][$index] ?? null;
    //             $lineTotal = ($price * $qty) - $disc;
    //             PurchaseItem::create([
    //                 'purchase_id'     => $purchase->id,
    //                 'product_id'      => $productId,
    //                 'unit'            => $unit,
    //                 'price'           => $price,
    //                 'item_discount'   => $disc,
    //                 'qty'             => $qty,
    //                 'line_total'      => $lineTotal,
    //             ]);
    //             $subtotal += $lineTotal;
    //             $stock = Stock::where('branch_id', auth()->user()->id)
    //                 ->where('warehouse_id', $validated['warehouse_id'])
    //                 ->where('product_id', $productId)
    //                 ->first();
    //             if ($stock) {
    //                 $stock->qty += $qty;
    //                 $stock->save();
    //             } else {
    //                 Stock::create([
    //                     'branch_id'       => auth()->user()->id,
    //                     'warehouse_id'    => $validated['warehouse_id'],
    //                     'product_id'      => $productId,
    //                     'qty'             => $qty,
    //                 ]);
    //             }
    //         }
    //         $discount = $request->discount ?? 0;
    //         $extraCost = $request->extra_cost ?? 0;
    //         $netAmount = ($subtotal - $discount) + $extraCost;
    //         $purchase->update([
    //             'subtotal'    => $subtotal,
    //             'discount'    => $discount,
    //             'extra_cost'  => $extraCost,
    //             'net_amount'  => $netAmount,
    //             'due_amount'  => $netAmount,
    //         ]);
    //         $previousLedger = VendorLedger::where('vendor_id', $validated['vendor_id'])
    //             ->latest('id')
    //             ->first();

    //         $openingBalance = $previousLedger ? $previousLedger->closing_balance : 0;
    //         $newClosingBalance = $openingBalance + (float)$netAmount;

    //         VendorLedger::create([
    //             'vendor_id'         => $validated['vendor_id'],
    //             'admin_or_user_id'  => auth()->id(),
    //             'opening_balance'   => $openingBalance,
    //             'previous_balance'  => $netAmount,
    //             'closing_balance'   => $newClosingBalance,
    //         ]);
    //         if ($gatepass) {
    //             $gatepass->purchase_id = $purchase->id;
    //             $gatepass->status = 'linked';
    //             $gatepass->save();
    //         }
    //     });
    //     return redirect()->route('InwardGatepass.home')->with('success', 'Purchase has been successfully added');
    // }

    public function edit($id)
    {
        $purchase   = Purchase::with('items.product')->findOrFail($id);
        $Vendor     = Vendor::all();
        $Warehouse  = Warehouse::all();

        return view('admin_panel.purchase.edit', compact('purchase', 'Vendor', 'Warehouse'));
    }


    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'invoice_no'      => 'nullable|string',
                'vendor_id'       => 'nullable|exists:vendors,id',
                'purchase_date'   => 'nullable|date',
                
                'purchase_to'     => 'required|in:shop,warehouse',
                'warehouse_id'    => 'nullable|required_if:purchase_to,warehouse|exists:warehouses,id',
                
                'note'            => 'nullable|string',
                'job_description' => 'nullable|string',
                'discount'        => 'nullable|numeric|min:0',
                'extra_cost'      => 'nullable|numeric|min:0',

                'product_id'      => 'array',
                'product_id.*'    => 'nullable|exists:products,id',

                'qty'             => 'array',
                'qty.*'           => 'nullable|required_with:product_id.*|numeric|min:1',

                'price'           => 'array',
                'price.*'         => 'nullable|required_with:product_id.*|numeric|min:0',

                'unit'            => 'array',
                'unit.*'          => 'nullable|required_with:product_id.*|string',

                'item_disc'       => 'nullable|array',
                'item_disc.*'     => 'nullable|numeric|min:0',
                
                'item_note'       => 'nullable|array',
                'item_note.*'     => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated, $request, $id) {
                $purchase = Purchase::findOrFail($id);

                // Pehle purchase ke purane items delete karenge
                $purchase->items()->delete();

                $subtotal = 0;

                // Naye items insert karna
                foreach ($validated['product_id'] as $index => $productId) {
                    $qty   = $validated['qty'][$index]   ?? 0;
                    $price = $validated['price'][$index] ?? 0;

                    if (empty($productId) || empty($qty) || empty($price)) {
                        continue;
                    }

                    $disc = $validated['item_disc'][$index] ?? 0;
                    $unit = $validated['unit'][$index] ?? null;
                    $lineTotal = ($price * $qty) - $disc;

                    PurchaseItem::create([
                        'purchase_id'   => $purchase->id,
                        'product_id'    => $productId,
                        'unit'          => $unit,
                        'price'         => $price,
                        'item_discount' => $disc,
                        'qty'           => $qty,
                        'note'          => $request->item_note[$index] ?? null,
                        'line_total'    => $lineTotal,
                    ]);

                    $subtotal += $lineTotal;
                }

                // Final calculations
                $discount  = (float) ($request->discount ?? 0);
                $extraCost = (float) ($request->extra_cost ?? 0);

                $netAmount = ($subtotal - $discount) + $extraCost;

                // Purchase table update
                $purchase->update([
                    'purchase_to'     => $validated['purchase_to'],
                    'warehouse_id'    => $validated['purchase_to'] === 'warehouse' ? $validated['warehouse_id'] : null,
                    'vendor_id'       => $validated['vendor_id'],
                    'purchase_date'   => $validated['purchase_date'],
                    'invoice_no'      => $validated['invoice_no'],
                    'company_invoice_no' => $request->company_invoice_no ?? $request->purchase_order_no,
                    'note'            => $validated['note'],
                    'job_description' => $validated['job_description'],
                    'subtotal'        => $subtotal,
                    'discount'        => $discount,
                    'extra_cost'      => $extraCost,
                    'net_amount'      => $netAmount,
                    'due_amount'      => $netAmount,
                ]);

                // Vendor Ledger Update
                $previousLedger = VendorLedger::where('vendor_id', $validated['vendor_id'])->first();
                $openingBalance = $previousLedger ? $previousLedger->closing_balance : 0;
                $newClosingBalance = $openingBalance + $netAmount;

                VendorLedger::updateOrCreate(
                    ['vendor_id' => $validated['vendor_id']],
                    [
                        'vendor_id'         => $validated['vendor_id'],
                        'admin_or_user_id'  => auth()->id(),
                        'previous_balance'  => $subtotal,
                        'closing_balance'   => $newClosingBalance,
                        'opening_balance'   => $openingBalance,
                    ]
                );
            });

            return redirect()->route('Purchase.home')->with('success', 'Purchase updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error occurred: ' . $e->getMessage());
        }
    }

    public function Invoice($id)
    {
        $purchase = Purchase::with('items.product')->findOrFail($id);
        $Vendor = Vendor::all();
        $Warehouse = Warehouse::all();
        return view('admin_panel.purchase.Invoice', compact('purchase', 'Vendor', 'Warehouse'));
    }



    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->delete();

        return redirect()->back()->with('success', 'Purchase deleted successfully.');
    }



    // purchase_reutun



    public function showReturnForm($id)
    {
        $purchase = Purchase::with(['vendor', 'warehouse', 'items.product'])->findOrFail($id);
        $Vendor = \App\Models\Vendor::all();
        $Warehouse = \App\Models\Warehouse::all();

        // compute already returned qty per product (for this purchase)
        foreach ($purchase->items as $item) {
            $returnedQty = \App\Models\PurchaseReturnItem::whereHas('purchaseReturn', function ($q) use ($purchase) {
                $q->where('purchase_id', $purchase->id);
            })->where('product_id', $item->product_id)->sum('qty');

            $item->available_qty = max(0, $item->qty - $returnedQty);
        }

        return view('admin_panel.purchase.purchase_return.create', compact('purchase', 'Vendor', 'Warehouse'));
    }


    // store return
    public function storeReturn(Request $request)
    {
        $validated = $request->validate([
            'purchase_id'  => 'required|exists:purchases,id',
            'vendor_id'    => 'required|exists:vendors,id',
            'return_date'  => 'required|date',

            // 🔥 CONDITIONAL warehouse
            'warehouse_id' => 'nullable|required_if:purchase_to,warehouse|exists:warehouses,id',

            'product_id'   => 'required|array',
            'product_id.*' => 'required|exists:products,id',

            'qty'          => 'required|array',
            'qty.*'        => 'required|numeric|min:0.01',

            'price'        => 'required|array',
            'price.*'      => 'required|numeric|min:0',

            'unit'         => 'required|array',
            'unit.*'       => 'required|string',

            'item_disc'    => 'nullable|array',
            'item_disc.*'  => 'nullable|numeric|min:0',

            'item_note'    => 'nullable|array',
            'item_note.*'  => 'nullable|string',

            'discount'     => 'nullable|numeric|min:0',
            'extra_cost'   => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {

            $last = \App\Models\PurchaseReturn::latest()->first();
            $invoice = 'RTN-' . str_pad(($last->id ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            $return = \App\Models\PurchaseReturn::create([
                'purchase_id'    => $validated['purchase_id'],
                'vendor_id'      => $validated['vendor_id'],
                'warehouse_id'   => $validated['warehouse_id'] ?? null, // ✅ SAFE
                'return_invoice' => $invoice,
                'return_date'    => $validated['return_date'],
                'remarks'        => $request->remarks ?? null,
            ]);

            $subtotal           = 0;
            $totalItemDiscount  = 0;

            foreach ($validated['product_id'] as $i => $productId) {

                $qty        = $validated['qty'][$i];
                $price      = $validated['price'][$i];
                $discPerPc  = $validated['item_disc'][$i] ?? 0;
                $unit       = $validated['unit'][$i];

                // ✅ correct discount calculation
                $itemDiscTotal = $discPerPc * $qty;
                $lineTotal     = ($price * $qty) - $itemDiscTotal;

                \App\Models\PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id'         => $productId,
                    'qty'                => $qty,
                    'price'              => $price,
                    'item_discount'      => $discPerPc,
                    'unit'               => $unit,
                    'note'               => $request->item_note[$i] ?? null, // ✅ NOTE
                    'line_total'         => $lineTotal,
                ]);


                $subtotal          += $lineTotal;
                $totalItemDiscount += $itemDiscTotal;

                // stock minus
                if ($request->purchase_to === 'warehouse') {

                    $stock = Stock::where('warehouse_id', $validated['warehouse_id'] ?? null)
                        ->where('product_id', $productId)
                        ->first();
                } else {

                    // SHOP stock
                    $stock = Stock::whereNull('warehouse_id')
                        ->where('product_id', $productId)
                        ->first();
                }


                if ($stock) {
                    $stock->qty -= $qty;
                    $stock->save();
                }
            }

            $overallDiscount = $validated['discount'] ?? 0;
            $extraCost       = $validated['extra_cost'] ?? 0;

            // ✅ final net
            $netAmount = ($subtotal - $overallDiscount) + $extraCost;

            $return->update([
                'bill_amount'    => $subtotal,
                'item_discount'  => $totalItemDiscount,
                'extra_discount' => $overallDiscount,
                'net_amount'     => $netAmount,
                'balance'        => $netAmount,
            ]);

            // Vendor Ledger Update for Return
            $ledger = \App\Models\VendorLedger::firstOrNew(['vendor_id' => $validated['vendor_id']]);
            
            if (!$ledger->exists) {
                $v = \App\Models\Vendor::find($validated['vendor_id']);
                $initialParams = $v ? $v->opening_balance : 0;
                $ledger->opening_balance = $initialParams;
                $ledger->closing_balance = $initialParams;
                $ledger->previous_balance = 0;
            }

            $ledger->admin_or_user_id = auth()->id();
            $ledger->previous_balance = $ledger->closing_balance;
            $ledger->closing_balance  -= $netAmount; // Subtract return amount
            $ledger->save();
        });

        return redirect()
            ->route('purchase.return.index')
            ->with('success', 'Purchase return successfully created');
    }

    public function purchaseReturnIndex()
    {
        $returns = \App\Models\PurchaseReturn::with(['vendor', 'warehouse', 'purchase', 'items.product'])
            ->latest()
            ->get();

        return view('admin_panel.purchase.purchase_return.index', compact('returns'));
    }


    public function ReturnInvoice($id)
    {
        $purchase_return = PurchaseReturn::with(['vendor', 'warehouse', 'items.product'])
            ->findOrFail($id);
        return view('admin_panel.purchase.purchase_return.return_Invoice', compact('purchase_return'));
    }
}
