<?php

namespace App\Http\Controllers;

use App\Models\InwardGatepass;
use App\Models\InwardGatepassItem;
use App\Models\InwardReturn;
use App\Models\InwardReturnItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InwardReturnController extends Controller
{
    public function index()
    {
        $inwardReturns = InwardReturn::with(['vendor', 'warehouse', 'inwardGatepass'])
            ->orderBy('id', 'desc')
            ->get();

        return view('admin_panel.inward_return.index', compact('inwardReturns'));
    }

    public function create($gatepass_id)
    {
        $gatepass = InwardGatepass::with(['items.product', 'vendor', 'warehouse', 'branch'])->findOrFail($gatepass_id);
        
        // Fetch previous returns for this gatepass to calculate available quantity
        $previousReturns = InwardReturn::with('items')->where('inward_gatepass_id', $gatepass->id)->get();
        
        $returnedQtys = [];
        foreach ($previousReturns as $ret) {
            foreach ($ret->items as $item) {
                if (!isset($returnedQtys[$item->product_id])) {
                    $returnedQtys[$item->product_id] = 0;
                }
                $returnedQtys[$item->product_id] += $item->qty;
            }
        }

        $items = [];
        foreach ($gatepass->items as $item) {
            $soldQty = $item->qty;
            $returnedQty = $returnedQtys[$item->product_id] ?? 0;
            $availableQty = max(0, $soldQty - $returnedQty);
            
            $items[] = [
                'product_id' => $item->product_id,
                'item_name' => $item->product->item_name ?? 'N/A',
                'item_code' => $item->product->item_code ?? 'N/A',
                'receive_type' => $item->receive_type ?? $gatepass->receive_type,
                'qty' => $soldQty,
                'returned' => $returnedQty,
                'available' => $availableQty,
                'unit' => $item->product->unit->name ?? ($item->product->unit_id ?? ''),
            ];
        }

        return view('admin_panel.inward_return.create', compact('gatepass', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'inward_gatepass_id' => 'required|exists:inward_gatepasses,id',
            'return_date' => 'required|date',
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        $productIds = $request->input('product_id', []);
        $returnQtys = $request->input('return_qty', []);
        $receiveTypes = $request->input('receive_type', []);

        // Check if anything is being returned
        $hasReturn = false;
        foreach ($returnQtys as $qty) {
            if ($qty > 0) {
                $hasReturn = true;
                break;
            }
        }

        if (!$hasReturn) {
            return back()->with('error', 'Please enter return quantity for at least one item.');
        }

        DB::beginTransaction();
        try {
            $gatepass = InwardGatepass::findOrFail($request->inward_gatepass_id);
            $nextInvoice = InwardReturn::generateInvoiceNo();

            $inwardReturn = InwardReturn::create([
                'inward_gatepass_id' => $gatepass->id,
                'vendor_id' => $request->vendor_id,
                'warehouse_id' => $request->warehouse_id,
                'return_invoice' => $nextInvoice,
                'return_date' => $request->return_date,
                'return_reason' => $request->return_reason,
                'transport' => $request->transport,
                'vehicle_no' => $request->vehicle_no,
                'driver_name' => $request->driver_name,
                'remarks' => $request->remarks,
            ]);

            for ($i = 0; $i < count($productIds); $i++) {
                $pid = $productIds[$i];
                $rQty = isset($returnQtys[$i]) ? (float)$returnQtys[$i] : 0;
                $rType = $receiveTypes[$i] ?? $gatepass->receive_type;

                if (!$pid || $rQty <= 0) continue;

                InwardReturnItem::create([
                    'inward_return_id' => $inwardReturn->id,
                    'product_id' => $pid,
                    'qty' => $rQty,
                    'receive_type' => $rType,
                ]);

                // Adjust Stock (Subtract from warehouse/shop)
                if ($rType === 'shop') {
                    $stock = Stock::where('branch_id', $gatepass->branch_id)
                        ->where('product_id', $pid)
                        ->first();

                    if ($stock) {
                        $stock->qty -= $rQty;
                        if ($stock->qty < 0) $stock->qty = 0;
                        $stock->save();
                    }
                } elseif ($rType === 'warehouse') {
                    $whStock = WarehouseStock::where('warehouse_id', $gatepass->warehouse_id)
                        ->where('product_id', $pid)
                        ->first();

                    if ($whStock) {
                        $whStock->quantity -= $rQty;
                        if ($whStock->quantity < 0) $whStock->quantity = 0;
                        $whStock->save();
                    }
                }
            }

            DB::commit();
            return redirect()->route('inward-returns.index')->with('success', 'Inward Return processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $inwardReturn = InwardReturn::with(['items.product.unit', 'items.product.brand', 'vendor', 'warehouse', 'inwardGatepass'])->findOrFail($id);
        return view('admin_panel.inward_return.show', compact('inwardReturn'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $inwardReturn = InwardReturn::with(['items', 'inwardGatepass'])->findOrFail($id);
            $gatepass = $inwardReturn->inwardGatepass;

            foreach ($inwardReturn->items as $item) {
                // Revert stock (Add back to warehouse/shop)
                if ($item->receive_type === 'shop' && $gatepass) {
                    $stock = Stock::where('branch_id', $gatepass->branch_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($stock) {
                        $stock->qty += $item->qty;
                        $stock->save();
                    }
                } elseif ($item->receive_type === 'warehouse' && $gatepass) {
                    $whStock = WarehouseStock::where('warehouse_id', $gatepass->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($whStock) {
                        $whStock->quantity += $item->qty;
                        $whStock->save();
                    }
                }
            }

            $inwardReturn->items()->delete();
            $inwardReturn->delete();

            DB::commit();
            return back()->with('success', 'Inward Return deleted and stock reverted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
