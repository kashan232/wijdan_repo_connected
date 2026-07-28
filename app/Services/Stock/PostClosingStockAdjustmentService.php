<?php

namespace App\Services\Stock;

use Illuminate\Support\Facades\DB;

class PostClosingStockAdjustmentService
{
    /**
     * Inventory movements strictly AFTER $closingDate (end of day).
     */
    public function calculate(string $closingDate): array
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
            if (($row->receive_type ?? '') === 'shop') {
                $addShopIn((int) $row->product_id, $qty);
            } else {
                $addWhIn((int) $row->product_id, (int) $row->warehouse_id, $qty);
            }
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

    public function shopNetAfter(array $adjustments, int $productId): float
    {
        return ($adjustments['shopIn'][$productId] ?? 0) - ($adjustments['shopOut'][$productId] ?? 0);
    }

    public function warehouseNetAfter(array $adjustments, int $productId, int $warehouseId): float
    {
        $in = $adjustments['whIn'][$productId][$warehouseId] ?? 0;
        $out = $adjustments['whOut'][$productId][$warehouseId] ?? 0;

        return $in - $out;
    }

    /**
     * Excel qty = stock as of $asOfDate. DB is stored as current so warehouse page (closing view) matches Excel.
     */
    public function storedShopFromExcelBaseline(float $excelShop, array $adjustments, int $productId): float
    {
        return max(0, round($excelShop + $this->shopNetAfter($adjustments, $productId), 4));
    }

    public function storedWarehouseFromExcelBaseline(float $excelWh, array $adjustments, int $productId, int $warehouseId): float
    {
        return max(0, round($excelWh + $this->warehouseNetAfter($adjustments, $productId, $warehouseId), 4));
    }
}
