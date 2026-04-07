<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $branchId  = auth()->id();
        $type      = $request->stock_type ?? 'all';
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        $query = DB::table('products')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->select(
                'products.id',
                'products.item_name',
                'products.unit_id',
                'products.price',
                'products.created_at',
                'brands.name as brand_name'
            );

        // Subquery for Shop Stock
        $shopSub = DB::table('stocks')
            ->selectRaw('COALESCE(SUM(qty), 0)')
            ->whereColumn('product_id', 'products.id')
            ->where('branch_id', $branchId);
        if ($startDate && $endDate) {
            $shopSub->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        $query->selectSub($shopSub, 'shop_stock');

        // Subquery for Warehouse Stock
        $whSub = DB::table('warehouse_stocks')
            ->selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('product_id', 'products.id');
        if ($startDate && $endDate) {
            $whSub->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        $query->selectSub($whSub, 'warehouse_stock');

        // Subquery for First Warehouse Name
        $whNameSub = DB::table('warehouse_stocks')
            ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->select('warehouses.warehouse_name')
            ->whereColumn('product_id', 'products.id')
            ->limit(1);
        $query->selectSub($whNameSub, 'warehouse_name');

        // Filter by type
        if ($type === 'shop') {
            $query->whereExists(function($q) use ($branchId, $startDate, $endDate) {
                $q->select(DB::raw(1))->from('stocks')->whereColumn('product_id', 'products.id')->where('branch_id', $branchId);
                if ($startDate && $endDate) $q->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            });
        } elseif ($type === 'warehouse') {
             $query->whereExists(function($q) use ($startDate, $endDate) {
                $q->select(DB::raw(1))->from('warehouse_stocks')->whereColumn('product_id', 'products.id');
                if ($startDate && $endDate) $q->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            });
        }

        $stocks = $query->orderBy('products.id', 'desc')->paginate(100)->withQueryString();

        return view('admin_panel.warehouses.warehouse_stocks.index', compact('stocks'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $products = Product::all();
        return view('admin_panel.warehouses.warehouse_stocks.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required',
            'product_id' => 'required',
            'quantity' => 'required|integer|min:0'
        ]);

        WarehouseStock::create($request->all());
        return redirect()->route('warehouse_stocks.index')->with('success', 'Stock added successfully.');
    }

    public function edit(WarehouseStock $warehouseStock)
    {
        $warehouses = Warehouse::all();
        $products = Product::all();
        return view('admin_panel.warehouses.warehouse_stocks.edit', compact('warehouseStock', 'warehouses', 'products'));
    }

    public function update(Request $request, WarehouseStock $warehouseStock)
    {
        $request->validate([
            'warehouse_id' => 'required',
            'product_id' => 'required',
            'quantity' => 'required|integer|min:0'
        ]);

        $warehouseStock->update($request->all());
        return redirect()->route('warehouse_stocks.index')->with('success', 'Stock updated successfully.');
    }

    public function destroy(WarehouseStock $warehouseStock)
    {
        $warehouseStock->delete();
        return redirect()->route('warehouse_stocks.index')->with('success', 'Stock deleted successfully.');
    }
}
