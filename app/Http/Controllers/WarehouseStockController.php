<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    private const UNIT_GROUPS = [
        'Piece' => ['Piece', 'Pieces', 'pisces'],
        'Meter' => ['Meter'],
        'Yard'  => ['Yard', 'Yards'],
    ];

    private function normalizeUnit(?string $unit): ?string
    {
        $unit = strtolower(trim($unit ?? ''));

        if (in_array($unit, ['piece', 'pieces', 'pisces'], true)) {
            return 'piece';
        }

        if ($unit === 'meter') {
            return 'meter';
        }

        if (in_array($unit, ['yard', 'yards'], true)) {
            return 'yard';
        }

        return null;
    }

    private function applyUnitFilter($query, ?string $unit): void
    {
        if (!$unit || !isset(self::UNIT_GROUPS[$unit])) {
            return;
        }

        $query->whereIn('products.unit_id', self::UNIT_GROUPS[$unit]);
    }

    private function buildStockQuery(Request $request, bool $applyUnitFilter = true)
    {
        $type          = $request->stock_type ?? 'all';
        $startDate     = $request->start_date;
        $endDate       = $request->end_date;
        $search        = $request->search;
        $categoryId    = $request->category_id;
        $subCategoryId = $request->subcategory_id;
        $brandIds      = array_filter((array) $request->brand_id);
        $unit          = $request->unit;

        $query = DB::table('products')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->select(
                'products.id',
                'products.item_name',
                'products.item_code',
                'products.barcode_path',
                'products.unit_id',
                'products.wholesale_price',
                'products.created_at',
                'brands.name as brand_name'
            );

        $query->selectRaw("
            COALESCE(
                NULLIF(
                    (
                        COALESCE((
                            SELECT SUM(pi.price * pi.qty)
                            FROM purchase_items pi
                            WHERE pi.product_id = products.id
                              AND pi.price > 0
                              AND pi.qty > 0
                        ), 0)
                        +
                        COALESCE((
                            SELECT SUM(igi.price * igi.qty)
                            FROM inward_gatepass_items igi
                            INNER JOIN inward_gatepasses ig ON ig.id = igi.inward_gatepass_id
                            WHERE igi.product_id = products.id
                              AND igi.price > 0
                              AND igi.qty > 0
                              AND ig.status = 'linked'
                              AND ig.bill_status = 'billed'
                        ), 0)
                    )
                    /
                    NULLIF(
                        (
                            COALESCE((
                                SELECT SUM(pi.qty)
                                FROM purchase_items pi
                                WHERE pi.product_id = products.id
                                  AND pi.price > 0
                                  AND pi.qty > 0
                            ), 0)
                            +
                            COALESCE((
                                SELECT SUM(igi.qty)
                                FROM inward_gatepass_items igi
                                INNER JOIN inward_gatepasses ig ON ig.id = igi.inward_gatepass_id
                                WHERE igi.product_id = products.id
                                  AND igi.price > 0
                                  AND igi.qty > 0
                                  AND ig.status = 'linked'
                                  AND ig.bill_status = 'billed'
                            ), 0)
                        ),
                        0
                    ),
                    0
                ),
                NULLIF(CAST(products.wholesale_price AS DECIMAL(12,2)), 0),
                0
            ) AS cost_price
        ");

        $query->selectRaw("
            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM purchase_items pi
                    WHERE pi.product_id = products.id AND pi.price > 0 AND pi.qty > 0
                ) OR EXISTS (
                    SELECT 1
                    FROM inward_gatepass_items igi
                    INNER JOIN inward_gatepasses ig ON ig.id = igi.inward_gatepass_id
                    WHERE igi.product_id = products.id
                      AND igi.price > 0
                      AND igi.qty > 0
                      AND ig.status = 'linked'
                      AND ig.bill_status = 'billed'
                ) THEN 'Avg Purchase'
                WHEN NULLIF(CAST(products.wholesale_price AS DECIMAL(12,2)), 0) IS NOT NULL THEN 'Wholesale'
                ELSE 'N/A'
            END AS price_source
        ");

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.item_name', 'like', "%{$search}%")
                  ->orWhere('products.item_code', 'like', "%{$search}%")
                  ->orWhere('products.barcode_path', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        if ($subCategoryId) {
            $query->where('products.sub_category_id', $subCategoryId);
        }

        if (!empty($brandIds)) {
            $query->whereIn('products.brand_id', $brandIds);
        }

        if ($applyUnitFilter) {
            $this->applyUnitFilter($query, $unit);
        }

        $shopSub = DB::table('stocks')
            ->selectRaw('COALESCE(SUM(qty), 0)')
            ->whereColumn('product_id', 'products.id');

        if ($type === 'warehouse' || is_numeric($type)) {
            $shopSub->whereRaw('1 = 0');
        }

        if ($startDate && $endDate) {
            $shopSub->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        $query->selectSub($shopSub, 'shop_stock');

        $whSub = DB::table('warehouse_stocks')
            ->selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('product_id', 'products.id');

        if ($type === 'shop') {
            $whSub->whereRaw('1 = 0');
        } elseif (is_numeric($type)) {
            $whSub->where('warehouse_id', $type);
        }

        if ($startDate && $endDate) {
            $whSub->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        $query->selectSub($whSub, 'warehouse_stock');

        $whNameSub = DB::table('warehouse_stocks')
            ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->select('warehouses.warehouse_name')
            ->whereColumn('product_id', 'products.id');

        if ($type === 'shop') {
            $whNameSub->whereRaw('1 = 0');
        } elseif (is_numeric($type)) {
            $whNameSub->where('warehouse_id', $type);
        }

        $whNameSub->limit(1);
        $query->selectSub($whNameSub, 'warehouse_name');

        if ($type === 'shop') {
            $query->whereExists(function ($q) use ($startDate, $endDate) {
                $q->select(DB::raw(1))->from('stocks')->whereColumn('product_id', 'products.id');
                if ($startDate && $endDate) {
                    $q->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                }
            });
        } elseif ($type === 'warehouse') {
            $query->whereExists(function ($q) use ($startDate, $endDate) {
                $q->select(DB::raw(1))->from('warehouse_stocks')->whereColumn('product_id', 'products.id');
                if ($startDate && $endDate) {
                    $q->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                }
            });
        } elseif (is_numeric($type)) {
            $query->whereExists(function ($q) use ($type, $startDate, $endDate) {
                $q->select(DB::raw(1))->from('warehouse_stocks')
                  ->whereColumn('product_id', 'products.id')
                  ->where('warehouse_id', $type);
                if ($startDate && $endDate) {
                    $q->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                }
            });
        }

        return $query;
    }

    private function calculateStockTotals(Request $request): array
    {
        $unitQuery = $this->buildStockQuery($request, false);
        $unitResults = $unitQuery->get();

        $totals = [
            'piece'                 => 0,
            'meter'                 => 0,
            'yard'                  => 0,
            'piece_value'           => 0,
            'meter_value'           => 0,
            'yard_value'            => 0,
            'total_shop'            => 0,
            'total_warehouse'       => 0,
            'total_stock'           => 0,
            'total_shop_value'      => 0,
            'total_warehouse_value' => 0,
            'total_stock_value'     => 0,
        ];

        foreach ($unitResults as $row) {
            $costPrice = (float) ($row->cost_price ?? 0);
            $totalQty = (float) $row->shop_stock + (float) $row->warehouse_stock;
            $normalized = $this->normalizeUnit($row->unit_id);

            if ($normalized) {
                $totals[$normalized] += $totalQty;
                $totals[$normalized . '_value'] += $totalQty * $costPrice;
            }
        }

        $summaryQuery = $this->buildStockQuery($request, true);
        $summaryResults = $summaryQuery->get();

        foreach ($summaryResults as $row) {
            $costPrice = (float) ($row->cost_price ?? 0);
            $shop = (float) $row->shop_stock;
            $wh = (float) $row->warehouse_stock;
            $totalQty = $shop + $wh;

            $totals['total_shop'] += $shop;
            $totals['total_warehouse'] += $wh;
            $totals['total_stock'] += $totalQty;
            $totals['total_shop_value'] += $shop * $costPrice;
            $totals['total_warehouse_value'] += $wh * $costPrice;
            $totals['total_stock_value'] += $totalQty * $costPrice;
        }

        return $totals;
    }

    public function exportAll(Request $request)
    {
        $query = $this->buildStockQuery($request);
        $stocks = $query->orderBy('products.id', 'desc')->get();

        $data = [];
        foreach ($stocks as $stock) {
            $remarks = '';
            if ($stock->warehouse_stock == 0 && $stock->shop_stock > 0) {
                $remarks = 'Shop Only';
            } elseif ($stock->warehouse_stock > 0 && $stock->shop_stock == 0) {
                $remarks = 'Warehouse Only';
            }

            $dateStr = \Carbon\Carbon::parse($stock->created_at)->format('d M Y');
            $costPrice = (float) ($stock->cost_price ?? 0);
            $shopStock = (float) $stock->shop_stock;
            $warehouseStock = (float) $stock->warehouse_stock;
            $totalStock = $shopStock + $warehouseStock;
            $stockValue = $totalStock * $costPrice;

            $data[] = [
                $dateStr,
                $stock->warehouse_name ?? '— Shop —',
                $stock->item_name . ' (' . $stock->item_code . ')',
                $stock->barcode_path ?? '',
                $stock->unit_id ?? '-',
                $stock->brand_name ?? 'N/A',
                $costPrice,
                $stock->price_source ?? 'N/A',
                $shopStock,
                $warehouseStock,
                $totalStock,
                $stockValue,
                $remarks,
            ];
        }

        return response()->json($data);
    }

    public function index(Request $request)
    {
        $warehouses = Warehouse::all();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $subcategories = collect();

        if ($request->category_id) {
            $subcategories = Subcategory::where('category_id', $request->category_id)
                ->orderBy('name')
                ->get();
        }

        $query = $this->buildStockQuery($request);
        $stockTotals = $this->calculateStockTotals($request);
        $stocks = $query->orderBy('products.id', 'desc')->paginate(100)->withQueryString();

        $viewData = compact('stocks', 'warehouses', 'categories', 'brands', 'subcategories', 'stockTotals');

        if ($request->ajax()) {
            return view('admin_panel.warehouses.warehouse_stocks.index', $viewData)->render();
        }

        return view('admin_panel.warehouses.warehouse_stocks.index', $viewData);
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
            'quantity' => 'required|integer|min:0',
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
            'quantity' => 'required|integer|min:0',
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
