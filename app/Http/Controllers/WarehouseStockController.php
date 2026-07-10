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

    private function isStockAdmin(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->id === 1 || $user->hasRole('Admin');
    }

    private function applyUnitFilter($query, ?string $unit): void
    {
        if (!$unit || !isset(self::UNIT_GROUPS[$unit])) {
            return;
        }

        $query->whereIn('products.unit_id', self::UNIT_GROUPS[$unit]);
    }

    private function resolveClosingEndDate(Request $request): string
    {
        return $request->closing_end_date ?: config('stock.yearly_closing_date', '2026-06-21');
    }

    private function calculatePostClosingAdjustments(string $closingDate): array
    {
        $shopIn = [];
        $shopOut = [];
        $whIn = [];
        $whOut = [];

        $addShopIn = function (int $productId, float $qty) use (&$shopIn) {
            $shopIn[$productId] = ($shopIn[$productId] ?? 0) + $qty;
        };
        $addShopOut = function (int $productId, float $qty) use (&$shopOut) {
            $shopOut[$productId] = ($shopOut[$productId] ?? 0) + $qty;
        };
        $addWhIn = function (int $productId, int $warehouseId, float $qty) use (&$whIn) {
            if ($warehouseId <= 0) {
                return;
            }
            $whIn[$productId][$warehouseId] = ($whIn[$productId][$warehouseId] ?? 0) + $qty;
        };
        $addWhOut = function (int $productId, int $warehouseId, float $qty) use (&$whOut) {
            if ($warehouseId <= 0) {
                return;
            }
            $whOut[$productId][$warehouseId] = ($whOut[$productId][$warehouseId] ?? 0) + $qty;
        };

        $purchasesAfter = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereNull('purchases.deleted_at')
            ->where('purchases.purchase_date', '>', $closingDate)
            ->select('purchase_items.product_id', 'purchase_items.qty', 'purchases.purchase_to', 'purchases.warehouse_id')
            ->get();

        foreach ($purchasesAfter as $row) {
            $qty = (float) $row->qty;
            if ($row->purchase_to === 'shop') {
                $addShopIn((int) $row->product_id, $qty);
            } else {
                $addWhIn((int) $row->product_id, (int) $row->warehouse_id, $qty);
            }
        }

        $inwardsAfter = DB::table('inward_gatepass_items')
            ->join('inward_gatepasses', 'inward_gatepasses.id', '=', 'inward_gatepass_items.inward_gatepass_id')
            ->where('inward_gatepasses.gatepass_date', '>', $closingDate)
            ->select(
                'inward_gatepass_items.product_id',
                'inward_gatepass_items.qty',
                'inward_gatepasses.warehouse_id',
                'inward_gatepasses.receive_type'
            )
            ->get();

        foreach ($inwardsAfter as $row) {
            $qty = (float) $row->qty;
            $addWhIn((int) $row->product_id, (int) $row->warehouse_id, $qty);
        }

        $purchaseReturnsAfter = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->leftJoin('purchases', 'purchases.id', '=', 'purchase_returns.purchase_id')
            ->where('purchase_returns.return_date', '>', $closingDate)
            ->select(
                'purchase_return_items.product_id',
                'purchase_return_items.qty',
                'purchases.purchase_to',
                'purchase_returns.warehouse_id'
            )
            ->get();

        foreach ($purchaseReturnsAfter as $row) {
            $qty = (float) $row->qty;
            if ($row->purchase_to === 'shop') {
                $addShopOut((int) $row->product_id, $qty);
            } else {
                $addWhOut((int) $row->product_id, (int) ($row->warehouse_id ?? 0), $qty);
            }
        }

        $inwardReturnsAfter = DB::table('inward_return_items')
            ->join('inward_returns', 'inward_returns.id', '=', 'inward_return_items.inward_return_id')
            ->where('inward_returns.return_date', '>', $closingDate)
            ->select(
                'inward_return_items.product_id',
                'inward_return_items.qty',
                'inward_return_items.receive_type',
                'inward_returns.warehouse_id'
            )
            ->get();

        foreach ($inwardReturnsAfter as $row) {
            $qty = (float) $row->qty;
            if ($row->receive_type === 'shop') {
                $addShopOut((int) $row->product_id, $qty);
            } else {
                $addWhOut((int) $row->product_id, (int) ($row->warehouse_id ?? 0), $qty);
            }
        }

        $codeToId = DB::table('products')->pluck('id', 'item_code')->toArray();

        $salesAfter = DB::table('sales')
            ->where('created_at', '>', $closingDate . ' 23:59:59')
            ->select('product', 'product_code', 'qty')
            ->get();

        foreach ($salesAfter as $sale) {
            $ids = array_map('trim', explode(',', (string) $sale->product));
            $codes = array_map('trim', explode(',', (string) $sale->product_code));
            $qtys = array_map('trim', explode(',', (string) $sale->qty));

            foreach ($qtys as $idx => $qtyValue) {
                $qty = (float) $qtyValue;
                if ($qty <= 0) {
                    continue;
                }

                if (!empty($ids[$idx]) && is_numeric($ids[$idx])) {
                    $addShopOut((int) $ids[$idx], $qty);
                    continue;
                }

                $code = $codes[$idx] ?? '';
                if ($code !== '' && isset($codeToId[$code])) {
                    $addShopOut((int) $codeToId[$code], $qty);
                }
            }
        }

        $saleReturnsAfter = DB::table('sales_returns')
            ->where('created_at', '>', $closingDate . ' 23:59:59')
            ->select('product', 'product_code', 'qty')
            ->get();

        foreach ($saleReturnsAfter as $saleReturn) {
            $ids = array_map('trim', explode(',', (string) $saleReturn->product));
            $codes = array_map('trim', explode(',', (string) $saleReturn->product_code));
            $qtys = array_map('trim', explode(',', (string) $saleReturn->qty));

            foreach ($qtys as $idx => $qtyValue) {
                $qty = (float) $qtyValue;
                if ($qty <= 0) {
                    continue;
                }

                if (!empty($ids[$idx]) && is_numeric($ids[$idx])) {
                    $addShopIn((int) $ids[$idx], $qty);
                    continue;
                }

                $code = $codes[$idx] ?? '';
                if ($code !== '' && isset($codeToId[$code])) {
                    $addShopIn((int) $codeToId[$code], $qty);
                }
            }
        }

        $transfersAfter = DB::table('stock_transfers')
            ->where('created_at', '>', $closingDate . ' 23:59:59')
            ->get();

        foreach ($transfersAfter as $transfer) {
            $productIds = json_decode($transfer->product_id, true);
            if (is_string($productIds)) {
                $productIds = json_decode($productIds, true);
            }
            if (!is_array($productIds)) {
                $productIds = [];
            }

            $quantities = json_decode($transfer->quantity, true);
            if (is_string($quantities)) {
                $quantities = json_decode($quantities, true);
            }
            if (!is_array($quantities)) {
                $quantities = [];
            }
            $fromWarehouse = (string) $transfer->from_warehouse_id;
            $transferTo = $transfer->transfer_to ?? ($transfer->to_shop ? 'shop' : 'warehouse');
            $toWarehouseId = (int) ($transfer->to_warehouse_id ?? 0);

            foreach ($productIds as $index => $productId) {
                $productId = (int) $productId;
                $qty = (float) ($quantities[$index] ?? 0);
                if ($productId <= 0 || $qty <= 0) {
                    continue;
                }

                if ($fromWarehouse === 'Shop' || (int) $fromWarehouse === 0) {
                    $addShopOut($productId, $qty);
                } else {
                    $addWhOut($productId, (int) $fromWarehouse, $qty);
                }

                if ($transferTo === 'shop') {
                    $addShopIn($productId, $qty);
                } elseif ($toWarehouseId > 0) {
                    $addWhIn($productId, $toWarehouseId, $qty);
                }
            }
        }

        return compact('shopIn', 'shopOut', 'whIn', 'whOut');
    }

    private function applyClosingAdjustments($stocks, array $adjustments, $warehouseFilter = null)
    {
        $shopIn = $adjustments['shopIn'];
        $shopOut = $adjustments['shopOut'];
        $whIn = $adjustments['whIn'];
        $whOut = $adjustments['whOut'];

        return $stocks->map(function ($stock) use ($shopIn, $shopOut, $whIn, $whOut, $warehouseFilter) {
            $productId = (int) $stock->id;

            $stock->shop_stock = max(
                0,
                (float) $stock->shop_stock
                    - ($shopIn[$productId] ?? 0)
                    + ($shopOut[$productId] ?? 0)
            );

            if ($warehouseFilter && is_numeric($warehouseFilter)) {
                $warehouseId = (int) $warehouseFilter;
                $stock->warehouse_stock = max(
                    0,
                    (float) $stock->warehouse_stock
                        - ($whIn[$productId][$warehouseId] ?? 0)
                        + ($whOut[$productId][$warehouseId] ?? 0)
                );
            } else {
                $totalWhIn = 0;
                $totalWhOut = 0;
                if (isset($whIn[$productId])) {
                    $totalWhIn = array_sum($whIn[$productId]);
                }
                if (isset($whOut[$productId])) {
                    $totalWhOut = array_sum($whOut[$productId]);
                }

                $stock->warehouse_stock = max(
                    0,
                    (float) $stock->warehouse_stock - $totalWhIn + $totalWhOut
                );
            }

            return $stock;
        });
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
        $closingEndDate = $this->resolveClosingEndDate($request);

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

        if ($this->isStockAdmin()) {
        $purchaseDateFilter = "
            AND EXISTS (
                SELECT 1 FROM purchases p
                WHERE p.id = pi.purchase_id
                  AND p.deleted_at IS NULL
                  AND p.purchase_date <= '{$closingEndDate}'
            )";
        $inwardDateFilter = "
            AND ig.gatepass_date <= '{$closingEndDate}'";

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
                              {$purchaseDateFilter}
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
                              {$inwardDateFilter}
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
                                  {$purchaseDateFilter}
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
                                  {$inwardDateFilter}
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
                    {$purchaseDateFilter}
                ) OR EXISTS (
                    SELECT 1
                    FROM inward_gatepass_items igi
                    INNER JOIN inward_gatepasses ig ON ig.id = igi.inward_gatepass_id
                    WHERE igi.product_id = products.id
                      AND igi.price > 0
                      AND igi.qty > 0
                      AND ig.status = 'linked'
                      AND ig.bill_status = 'billed'
                      {$inwardDateFilter}
                ) THEN 'Avg Purchase'
                WHEN NULLIF(CAST(products.wholesale_price AS DECIMAL(12,2)), 0) IS NOT NULL THEN 'Wholesale'
                ELSE 'N/A'
            END AS price_source
        ");
        }

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
            $query->whereBetween('products.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
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
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('stocks')->whereColumn('product_id', 'products.id');
            });
        } elseif ($type === 'warehouse') {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('warehouse_stocks')->whereColumn('product_id', 'products.id');
            });
        } elseif (is_numeric($type)) {
            $query->whereExists(function ($q) use ($type) {
                $q->select(DB::raw(1))->from('warehouse_stocks')
                  ->whereColumn('product_id', 'products.id')
                  ->where('warehouse_id', $type);
            });
        }

        return $query;
    }

    private function getAdjustedStocks(Request $request)
    {
        $closingEndDate = $this->resolveClosingEndDate($request);
        $adjustments = $this->calculatePostClosingAdjustments($closingEndDate);
        $type = $request->stock_type ?? 'all';
        $warehouseFilter = is_numeric($type) ? $type : null;
        $stocks = $this->buildStockQuery($request)->orderBy('products.id', 'desc')->get();

        return $this->applyClosingAdjustments($stocks, $adjustments, $warehouseFilter);
    }

    private function calculateStockTotals($stocks): array
    {
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

        foreach ($stocks as $row) {
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

            $normalized = $this->normalizeUnit($row->unit_id);
            if ($normalized) {
                $totals[$normalized] += $totalQty;
                $totals[$normalized . '_value'] += $totalQty * $costPrice;
            }
        }

        return $totals;
    }

    public function exportAll(Request $request)
    {
        $isAdmin = $this->isStockAdmin();
        $stocks = $this->getAdjustedStocks($request);

        $data = [];
        foreach ($stocks as $stock) {
            $remarks = '';
            if ($stock->warehouse_stock == 0 && $stock->shop_stock > 0) {
                $remarks = 'Shop Only';
            } elseif ($stock->warehouse_stock > 0 && $stock->shop_stock == 0) {
                $remarks = 'Warehouse Only';
            }

            $dateStr = \Carbon\Carbon::parse($stock->created_at)->format('d M Y');
            $shopStock = (float) $stock->shop_stock;
            $warehouseStock = (float) $stock->warehouse_stock;
            $totalStock = $shopStock + $warehouseStock;

            if ($isAdmin) {
                $costPrice = (float) ($stock->cost_price ?? 0);
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
            } else {
                $data[] = [
                    $dateStr,
                    $stock->warehouse_name ?? '— Shop —',
                    $stock->item_name . ' (' . $stock->item_code . ')',
                    $stock->barcode_path ?? '',
                    $stock->unit_id ?? '-',
                    $stock->brand_name ?? 'N/A',
                    $shopStock,
                    $warehouseStock,
                    $totalStock,
                    $remarks,
                ];
            }
        }

        return response()->json([
            'rows'     => $data,
            'is_admin' => $isAdmin,
        ]);
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

        $isAdmin = $this->isStockAdmin();
        $closingEndDate = $this->resolveClosingEndDate($request);
        $stocks = $this->getAdjustedStocks($request);
        $stockTotals = $isAdmin ? $this->calculateStockTotals($stocks) : [];

        $viewData = compact(
            'stocks',
            'warehouses',
            'categories',
            'brands',
            'subcategories',
            'stockTotals',
            'isAdmin',
            'closingEndDate'
        );

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
