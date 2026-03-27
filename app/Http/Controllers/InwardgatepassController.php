<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\InwardGatepass;
use App\Models\InwardGatepassItem;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InwardgatepassController extends Controller
{
    public function pdf($id)
    {
        $gatepass = InwardGatepass::with(['branch', 'warehouse', 'vendor', 'items.product'])->findOrFail($id);
        $pdf = Pdf::loadView('admin_panel.inward.pdf', compact('gatepass'));
        return $pdf->download('gatepass_' . $gatepass->id . '.pdf');
    }

    // 1. List all inward gatepasses
    // 1. List all inward gatepasses
    public function index(Request $request)
    {
        $query = InwardGatepass::with('items.product', 'branch', 'warehouse', 'vendor');

        // Filters
        if ($request->filled('start_date')) {
            $query->whereDate('gatepass_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('gatepass_date', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting: Pending first, then Latest
        $gatepasses = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                            ->orderBy('id', 'desc')
                            ->get();

        return view('admin_panel.inward.index', compact('gatepasses'));
    }

    // 2. Show create form
    public function create()
    {
        $branches   = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $vendors    = Vendor::orderBy('name')->get();

        return view('admin_panel.inward.create', compact('branches', 'warehouses', 'vendors'));
    }

    // 3. Store gatepass + items + update stock
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'branch_id'      => 'required|exists:branches,id',
    //         'warehouse_id'   => 'required|exists:warehouses,id',
    //         'vendor_id'      => 'required|exists:vendors,id',
    //         'gatepass_date'  => 'required|date',
    //         'product_id'     => 'required|array|min:1',
    //         'product_id.*'   => 'required|exists:products,id',
    //         'qty'            => 'required|array',
    //         'qty.*'          => 'required|integer|min:1',
    //     ]);

    //     DB::transaction(function () use ($request) {
    //         $gatepass = InwardGatepass::create([
    //             'branch_id'    => $request->branch_id,
    //             'warehouse_id' => $request->warehouse_id,
    //             'vendor_id'    => $request->vendor_id,
    //             'gatepass_date'=> $request->gatepass_date,
    //             'note'         => $request->note ?? null,
    //             'transport_name'=> $request->transport_name ?? null,
    //             'created_by'   => auth()->id() ?? null,
    //         ]);

    //         $productIds = $request->input('product_id', []);
    //         $qtys       = $request->input('qty', []);

    //         for ($i = 0; $i < count($productIds); $i++) {
    //             $pid = $productIds[$i];
    //             $q   = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
    //             if (!$pid || $q <= 0) continue;

    //             InwardGatepassItem::create([
    //                 'inward_gatepass_id' => $gatepass->id,
    //                 'product_id'         => $pid,
    //                 'qty'                => $q,
    //             ]);

    //             $stock = Stock::firstOrNew([
    //                 'branch_id'    => $request->branch_id,
    //                 'warehouse_id' => $request->warehouse_id,
    //                 'product_id'   => $pid,
    //             ]);
    //             $stock->qty = ($stock->qty ?? 0) + $q;
    //             $stock->save();
    //         }
    //     });

    //     return redirect()->route('InwardGatepass.home')
    //                      ->with('success','Inward Gatepass Created Successfully');
    // }
    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $nextInvoice = InwardGatepass::generateInvoiceNo();

            $gatepass = InwardGatepass::create([
                'invoice_no'      => $nextInvoice,
                'branch_id'    => $request->branch_id,
                'warehouse_id' => $request->warehouse_id,
                'vendor_id'    => $request->vendor_id,
                'gatepass_date' => $request->gatepass_date,
                'transport_name' => $request->transport_name,
                'gatepass_no' => $request->bilty_no,
                'remarks'         => $request->note ?? null,
                'receive_type' => $request->receive_type,
                'created_by'   => auth()->id() ?? null,
            ]);

            $productIds = $request->input('product_id', []);
            $qtys       = $request->input('qty', []);

            for ($i = 0; $i < count($productIds); $i++) {
                $pid = $productIds[$i];
                $q   = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
                if (!$pid || $q <= 0) continue;

                InwardGatepassItem::create([
                    'inward_gatepass_id' => $gatepass->id,
                    'product_id'         => $pid,
                    'qty'                => $q,
                ]);

                $ws = \App\Models\WarehouseStock::where('warehouse_id', $request->warehouse_id)
                    ->where('product_id', $pid)
                    ->first();

                if ($ws) {
                    // increment existing aggregated row
                    $ws->quantity = ($ws->quantity ?? 0) + $q;
                    // optionally update price/remarks if you want latest values
                    // $ws->price = $request->price[$i] ?? $ws->price;
                    // $ws->remarks = 'Inward via gatepass ' . ($request->bilty_no ?? '');
                    $ws->save();
                } else {
                    // create new aggregated row for this warehouse+product
                    \App\Models\WarehouseStock::create([
                        'warehouse_id' => $request->warehouse_id,
                        'product_id'   => $pid,
                        'quantity'     => $q,
                        'price'        => null,
                        'remarks'      => 'Inward via Gatepass: ' . ($request->bilty_no ?? ''),
                    ]);
                }
            }

            // Change status to 'pending' after inward gatepass is created
            $gatepass->status = 'pending'; // Pending until bill is created
            $gatepass->save();
        });

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Created Successfully');
    }


    // 4. Show single gatepass
    public function show($id)
    {
        $gatepass = InwardGatepass::with('items.product', 'branch', 'warehouse', 'vendor')->findOrFail($id);
        return view('admin_panel.inward.show', compact('gatepass'));
    }

    public function show_inv($id)
    {
        $gatepass = InwardGatepass::with('items.product', 'branch', 'warehouse', 'vendor')->findOrFail($id);
        return view('admin_panel.inward.invoice', compact('gatepass'));
    }

    // 5. Edit gatepass
    public function edit($id)
    {
        $gatepass = InwardGatepass::with('items')->findOrFail($id);
        $branches   = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $vendors    = Vendor::orderBy('name')->get();
        return view('admin_panel.inward.edit', compact('gatepass', 'branches', 'warehouses', 'vendors'));
    }

    // 6. Update gatepass + adjust stock
    public function update(Request $request, $id)
    {
        $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'vendor_id'     => 'required|exists:vendors,id',
            'gatepass_date' => 'required|date',
            'receive_type'  => 'required|in:warehouse,shop',
            'warehouse_id'  => 'required_if:receive_type,warehouse|nullable|exists:warehouses,id',
        ]);

        $gatepass = InwardGatepass::findOrFail($id);

        // ❌ do not allow edit after items added
        if ($gatepass->items()->count() > 0) {
            return back()->with('error', 'Items already added. You cannot edit header.');
        }

        $gatepass->update([
            'branch_id'      => $request->branch_id,
            'warehouse_id'   => $request->receive_type === 'warehouse' ? $request->warehouse_id : null,
            'vendor_id'      => $request->vendor_id,
            'gatepass_date'  => $request->gatepass_date,
            'gatepass_no'    => $request->gatepass_no,
            'remarks'        => $request->remarks,
            'transport_name' => $request->transport_name,
            'receive_type'   => $request->receive_type,
        ]);

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Gatepass updated successfully');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            $gatepass = InwardGatepass::with('items')->findOrFail($id);

            foreach ($gatepass->items as $item) {

                // ===== SHOP STOCK REVERSE =====
                if ($gatepass->receive_type === 'shop') {

                    $stock = Stock::where('branch_id', $gatepass->branch_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($stock) {
                        $stock->qty -= $item->qty;

                        // safety: negative na ho
                        if ($stock->qty < 0) {
                            $stock->qty = 0;
                        }

                        $stock->save();
                    }
                }

                // ===== WAREHOUSE STOCK REVERSE =====
                if ($gatepass->receive_type === 'warehouse') {

                    $whStock = WarehouseStock::where('warehouse_id', $gatepass->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($whStock) {
                        $whStock->quantity -= $item->qty;

                        if ($whStock->quantity < 0) {
                            $whStock->quantity = 0;
                        }

                        $whStock->save();
                    }
                }
            }

            // Delete items first
            InwardGatepassItem::where('inward_gatepass_id', $gatepass->id)->delete();

            // Delete gatepass
            $gatepass->delete();
        });

        return redirect()->back()->with('success', 'Inward Gatepass deleted & stock reversed successfully');
    }


    // 8. Search products
    public function searchProducts(Request $request)
    {
        $q = $request->get('q');
        $products = Product::with('brand')
            ->where('item_name', 'like', "%{$q}%")
            ->orWhere('item_code', 'like', "%{$q}%")
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function addDetails($id)
    {
        $gatepass = InwardGatepass::with([
            'branch',
            'vendor',
            'warehouse',
            'items.product' // ✅ VERY IMPORTANT
        ])->findOrFail($id);
        if ($gatepass->status !== 'pending') {
            abort(403, 'Details already added');
        }
        return view('admin_panel.inward.add_details', compact('gatepass'));
    }
    public function searchByBarcode(Request $request)
    {
        $barcode = trim($request->barcode);

        if (!$barcode) {
            return response()->json(null);
        }

        $product = Product::with(['brand', 'unit', 'activeDiscount'])
            ->where('barcode_path', $barcode)
            ->first();

        if (!$product) {
            return response()->json(null);
        }

        $finalPrice = $product->activeDiscount
            ? $product->activeDiscount->final_price
            : $product->price;

        return response()->json([
            'id'        => $product->id,
            'name'      => $product->item_name,
            'code'      => $product->item_code,
            'brand'     => $product->brand->name ?? '',
            'unit'      => $product->unit_id ?? '',
            'wholesale_price'      => $product->wholesale_price ?? '',
            'price'     => $finalPrice,
            'has_discount' => $product->activeDiscount ? true : false,
        ]);
    }


    public function storeDetails(Request $request, $id)
    {
        $gatepass = InwardGatepass::findOrFail($id);
        DB::transaction(function () use ($request, $gatepass) {

            // 1. Handle Deleted Items
            // Get IDs currently in DB
            $dbIds = $gatepass->items()->pluck('id')->toArray();

            // Get IDs submitted in form
            $submittedIds = array_filter($request->existing_item_id ?? [], function ($val) {
                return !empty($val);
            });

            // Calculate diff
            $idsToDelete = array_diff($dbIds, $submittedIds);

            if (!empty($idsToDelete)) {
                $itemsToDelete = InwardGatepassItem::whereIn('id', $idsToDelete)->get();
                foreach ($itemsToDelete as $delItem) {
                    // Reverse stock (subtract existing qty)
                    $this->adjustStock($gatepass, $delItem->product_id, -($delItem->qty));
                    $delItem->delete();
                }
            }

            foreach ($request->product_id as $i => $pid) {

                $unit = $request->unit[$i] ?? null;

                if ($unit === 'Piece') {
                    $qty = (int) ($request->qty[$i] ?? 0);   // integer only
                } else {
                    $qty = (float) ($request->qty[$i] ?? 0); // allow decimal
                }

                $note = $request->note[$i] ?? null;
                $existingItemId = $request->existing_item_id[$i] ?? null;

                if (!$pid || $qty <= 0) continue;

                if ($existingItemId) {

                    $item = InwardGatepassItem::where('id', $existingItemId)
                        ->where('inward_gatepass_id', $gatepass->id)
                        ->first();

                    if (!$item) continue;

                    $oldQty = $item->qty;
                    $diff   = $qty - $oldQty;

                    $item->update([
                        'qty'  => $qty,
                        'note' => $note,
                    ]);

                    if ($diff != 0) {
                        $this->adjustStock($gatepass, $pid, $diff);
                    }
                } else {

                    InwardGatepassItem::create([
                        'inward_gatepass_id' => $gatepass->id,
                        'product_id'         => $pid,
                        'qty'                => $qty,
                        'note'               => $note,
                    ]);

                    $this->adjustStock($gatepass, $pid, $qty);
                }
            }


            // keep gatepass editable
            $gatepass->update(['status' => 'pending']);
        });

        return redirect()->route('InwardGatepass.home')->with('success', 'Items updated successfully');
    }

    private function adjustStock($gatepass, $productId, $qty)
    {
        // 🏪 SHOP STOCK
        if ($gatepass->receive_type === 'shop') {

            $stock = \App\Models\Stock::firstOrCreate(
                [
                    'branch_id'  => $gatepass->branch_id,
                    'product_id' => $productId,
                ],
                [
                    'warehouse_id' => $gatepass->warehouse_id,
                    'qty'          => 0,
                    'reserved_qty' => 0,
                ]
            );

            $stock->qty += $qty;
            $stock->save();
        }

        // 🏬 WAREHOUSE STOCK
        if ($gatepass->receive_type === 'warehouse') {

            $ws = \App\Models\WarehouseStock::firstOrCreate(
                [
                    'warehouse_id' => $gatepass->warehouse_id,
                    'product_id'   => $productId,
                ],
                [
                    'quantity' => 0,
                    'remarks'  => 'Inward via Gatepass ' . $gatepass->invoice_no,
                ]
            );

            $ws->quantity += $qty;
            $ws->save();
        }
    }

    // ------------------------------------------------------------------
    // NOTIFICATION LOGIC
    // ------------------------------------------------------------------

    public function checkNewInwards()
    {
        // Only trigger for admin
        if (auth()->check() && auth()->user()->email === 'admin@admin.com') {
            
            $newInwards = InwardGatepass::with('vendor', 'warehouse')
                ->where('admin_notified', 0)
                ->orderBy('created_at', 'desc')
                ->get();
    
            return response()->json($newInwards);
        }
        return response()->json([]);
    }

    public function markInwardsNotified(Request $request)
    {
        if (auth()->check() && auth()->user()->email === 'admin@admin.com') {
            $ids = $request->input('ids', []);
            if (!empty($ids)) {
                InwardGatepass::whereIn('id', $ids)->update(['admin_notified' => 1]);
            }
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error'], 403);
    }
}
