<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockTransfer;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse'])
            ->orderByDesc('id')
            ->when($request->start_date && $request->end_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            })
            ->get()
            ->map(function ($transfer) {

                // 🔐 SAFE decode
                if (is_string($transfer->product_id)) {
                    $productIds = json_decode($transfer->product_id, true);
                } elseif (is_array($transfer->product_id)) {
                    $productIds = $transfer->product_id;
                } else {
                    $productIds = [];
                }

                if (is_string($transfer->quantity)) {
                    $quantities = json_decode($transfer->quantity, true);
                } elseif (is_array($transfer->quantity)) {
                    $quantities = $transfer->quantity;
                } else {
                    $quantities = [];
                }

                // ensure arrays
                $productIds = is_array($productIds) ? $productIds : [];
                $quantities = is_array($quantities) ? $quantities : [];

                // product_id => qty map
                $qtyMap = [];
                foreach ($productIds as $i => $pid) {
                    $qtyMap[(int)$pid] = (float) ($quantities[$i] ?? 0);
                }

                // products in SAME order
                $items = \App\Models\Product::whereIn('id', $productIds)
                    ->get()
                    ->sortBy(fn($p) => array_search($p->id, $productIds))
                    ->values()
                    ->map(function ($product) use ($qtyMap) {
                        return [
                            'name' => $product->item_name,
                            'qty'  => $qtyMap[$product->id] ?? 0,
                            'unit' => $product->unit_id,
                        ];
                    });

                $transfer->items = $items;

                return $transfer;
            });

        return view(
            'admin_panel.warehouses.stock_transfers.index',
            compact('transfers')
        );
    }




    public function create()
    {
        $warehouses = Warehouse::all();
        $products = Product::all();
        return view('admin_panel.warehouses.stock_transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        try {
            $productIds = $request->product_id;
            $quantities = $request->quantity;

            $request->validate([
                'transfer_to'  => 'required|in:shop,warehouse',
                'product_id'   => 'required|array|min:1',
                'product_id.*' => 'required|integer|exists:products,id',
                'quantity'     => 'required|array',
                'quantity.*'   => 'required|numeric|min:0.01',
            ]);

            $fromWarehouse = $request->from_warehouse_id;
            $transferTo    = $request->transfer_to;
            $toWarehouse   = $request->to_warehouse_id;
            $remarks       = $request->remarks;

            $products   = $request->product_id;
            $quantities = $request->quantity;

            foreach ($productIds as $index => $productId) {

                // skip empty row safely
                if (empty($productId) || empty($quantities[$index])) {
                    continue;
                }

                $qty = (float) $quantities[$index];

                if ($qty <= 0) {
                    continue;
                }

                // ---------- SOURCE ----------
                if ($fromWarehouse !== 'Shop') {

                    $sourceStock = WarehouseStock::firstOrCreate(
                        [
                            'warehouse_id' => $fromWarehouse,
                            'product_id'   => $productId
                        ],
                        [
                            'quantity' => 0,
                            'price'    => 0
                        ]
                    );

                    $sourceStock->quantity -= $qty;
                    $sourceStock->save();
                } else {

                    $sourceStock = Stock::firstOrCreate(
                        ['product_id' => $productId],
                        ['qty' => 0]
                    );

                    $sourceStock->qty -= $qty;
                    $sourceStock->save();
                }

                // ---------- DESTINATION ----------
                if ($transferTo === 'warehouse' && $toWarehouse) {

                    $destStock = WarehouseStock::firstOrCreate(
                        [
                            'warehouse_id' => $toWarehouse,
                            'product_id'   => $productId
                        ],
                        [
                            'quantity' => 0,
                            'price'    => $sourceStock->price ?? 0
                        ]
                    );

                    $destStock->quantity += $qty;
                    $destStock->save();
                } elseif ($transferTo === 'shop') {

                    $shopStock = Stock::firstOrCreate(
                        ['product_id' => $productId],
                        ['qty' => 0]
                    );

                    $shopStock->qty += $qty;
                    $shopStock->save();
                }
            }
            // Combine transfer_date with current time to avoid 00:00:00 issue
            $transferDate = $request->transfer_date ?? date('Y-m-d');
            $createdAt = \Carbon\Carbon::parse($transferDate . ' ' . now()->format('H:i:s'));

            $transfer = StockTransfer::create([
                'from_warehouse_id' => $fromWarehouse === 'Shop' ? 0 : $fromWarehouse,
                'transfer_to'       => $transferTo,
                'to_warehouse_id'   => $transferTo === 'warehouse' ? $toWarehouse : null,
                'product_id'        => json_encode(array_values(array_filter($productIds))),
                'quantity'          => json_encode(array_values(array_filter($quantities))),
                'remarks'           => $remarks,
                'created_at'        => $createdAt,
                'updated_at'        => now(),
            ]);

            return redirect()
                ->route('recipt.warehouse', $transfer->id)
                ->with('success', 'Stock transferred successfully.');
        } catch (\Throwable $e) {

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }






    public function destroy(StockTransfer $stockTransfer)
    {
        // Optional: reverse the transfer if needed
        return back()->with('error', 'Deleting transfers not allowed.');
    }
    public function getStockQuantity(Request $request)
    {
        $productId   = $request->product_id;
        $warehouseId = $request->warehouse_id; // may be null or "Shop"

        // WAREHOUSE CASE (ignore "Shop")
        if (!empty($warehouseId) && $warehouseId !== 'Shop') {
            $stock = \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->first();

            return response()->json([
                'quantity' => $stock ? $stock->quantity : 0,
                'source'   => 'warehouse'
            ]);
        }

        // SHOP CASE (warehouse_id is null or "Shop")
        $stock = \App\Models\Stock::where('product_id', $productId)->first();

        return response()->json([
            'quantity' => $stock ? $stock->qty : 0,
            'source'   => 'shop'
        ]);
    }




    public function receipt($id)
    {
        $transfer = StockTransfer::with(['fromWarehouse', 'toWarehouse'])
            ->findOrFail($id);

        $productIds = json_decode($transfer->product_id, true) ?? [];
        $quantities = json_decode($transfer->quantity, true) ?? [];

        // Product ID => Qty
        $qtyMap = [];
        foreach ($productIds as $i => $pid) {
            $qtyMap[(int)$pid] = (float) ($quantities[$i] ?? 0);
        }

        // Products in correct order + attach qty
        $products = \App\Models\Product::whereIn('id', $productIds)
            ->get()
            ->sortBy(fn($p) => array_search($p->id, $productIds))
            ->values()
            ->map(function ($product) use ($qtyMap) {
                $product->transfer_qty = $qtyMap[$product->id] ?? 0;
                return $product;
            });

        /*
    |--------------------------------------------------------------------------
    | UNIT WISE TOTAL
    |--------------------------------------------------------------------------
    */
        $unitTotals = [];
        foreach ($products as $product) {
            $unit = $product->unit_id ?? 'Unit';
            $unitTotals[$unit] = ($unitTotals[$unit] ?? 0) + $product->transfer_qty;
        }

        return view(
            'admin_panel.warehouses.stock_transfers.receipt',
            compact('transfer', 'products', 'unitTotals')
        );
    }

    // ------------------------------------------------------------------
    // NOTIFICATION LOGIC
    // ------------------------------------------------------------------

    public function checkNewTransfers()
    {
        // Only trigger for admin
        if (auth()->check() && auth()->user()->email === 'admin@admin.com') {
            
            $newTransfers = StockTransfer::with(['fromWarehouse', 'toWarehouse'])
                ->where('admin_notified', 0)
                ->orderBy('created_at', 'desc')
                ->get();
    
            return response()->json($newTransfers);
        }
        return response()->json([]);
    }

    public function markTransfersNotified(Request $request)
    {
        if (auth()->check() && auth()->user()->email === 'admin@admin.com') {
            $ids = $request->input('ids', []);
            if (!empty($ids)) {
                StockTransfer::whereIn('id', $ids)->update(['admin_notified' => 1]);
            }
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error'], 403);
    }
}
