<?php

namespace App\Services\PeriodClosing;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ArchiveListingService
{
    public function salesDataTable(Request $request, int $periodId): array
    {
        $query = Sale::with(['customer_relation', 'user'])
            ->where('accounting_period_id', $periodId);

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->from_date)->startOfDay());
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->to_date)->endOfDay());
        }
        if ($request->filled('filter_user')) {
            $query->where('user_id', $request->filter_user);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('total_bill_amount', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%")
                    ->orWhereHas('customer_relation', fn ($c) => $c->where('customer_name', 'like', "%{$search}%"));

                foreach (Product::where('item_name', 'like', "%{$search}%")->orWhere('barcode_path', 'like', "%{$search}%")->pluck('id') as $pid) {
                    $q->orWhereRaw('FIND_IN_SET(?, product)', [$pid]);
                }
            });
        }

        $totalRecords = Sale::where('accounting_period_id', $periodId)->count();
        $filteredRecords = (clone $query)->count();
        $totalFilteredSale = (clone $query)->sum('total_net');

        $this->applySaleOrdering($query, $request);
        $skip = $request->start ?? 0;
        $take = $request->length ?? 10;
        $sales = $query->skip($skip)->take($take)->get();
        $productsMap = $this->productMapFromSales($sales);
        $fmt = $this->fmt();

        $data = [];
        foreach ($sales as $index => $sale) {
            $prodIds = explode(',', $sale->product ?? '');
            $barcodeHtml = $productHtml = '';
            foreach ($prodIds as $pid) {
                $p = $productsMap[trim($pid)] ?? null;
                $barcodeHtml .= ($p ? $p->barcode_path : 'N/A') . '<br>';
                $productHtml .= ($p ? $p->item_name : 'N/A') . '<br>';
            }

            $statusBadge = $sale->sale_status === null
                ? '<span class="badge bg-success">Sale</span>'
                : ($sale->sale_status == 1 ? '<span class="badge bg-danger">Return</span>' : '<span class="badge bg-secondary">Unknown</span>');
            if ($sale->card > 0) {
                $statusBadge .= ' <span class="badge bg-info">Card</span>';
            }

            $extraDiscount = (float) ($sale->total_extradiscount ?? 0);
            $data[] = [
                $skip + $index + 1,
                $sale->user?->name ?? 'N/A',
                $sale->invoice_no,
                $sale->customer_relation->customer_name ?? 'Walk-in Customer',
                $sale->reference,
                $barcodeHtml,
                $productHtml,
                implode('<br>', array_map(fn ($q) => $fmt($q), explode(',', $sale->qty ?? ''))),
                implode('<br>', array_map(fn ($pr) => $fmt($pr), explode(',', $sale->per_price ?? ''))),
                implode('<br>', array_map(fn ($d) => $fmt($d), explode(',', $sale->per_discount ?? ''))),
                implode('<br>', array_map(fn ($t) => $fmt($t), explode(',', $sale->per_total ?? ''))),
                $extraDiscount > 0 ? $fmt($extraDiscount) : '—',
                '<span class="fw-bold fs-5">' . $fmt($sale->total_net) . '</span>',
                Carbon::parse($sale->created_at)->format('d-m-Y h:i A'),
                $statusBadge,
                '<div class="btn-group gap-1"><a href="' . route('sales.invoice', $sale->id) . '" target="_blank" class="btn btn-dark btn-mini text-white">Invoice</a>
                <a href="' . route('sales.dc', $sale->id) . '" target="_blank" class="btn btn-success btn-mini text-white">DC</a></div>',
            ];
        }

        return [
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'totalFilteredSale' => $totalFilteredSale,
        ];
    }

    public function salesReturnsDataTable(Request $request, int $periodId): array
    {
        $query = SalesReturn::with('sale.customer_relation')
            ->where('accounting_period_id', $periodId);

        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('sale', function ($saleQuery) use ($search) {
                    $saleQuery->where('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('customer_relation', fn ($c) => $c->where('customer_name', 'like', "%{$search}%"));
                })->orWhere('return_note', 'like', "%{$search}%");
            });
        }

        $totalRecords = SalesReturn::where('accounting_period_id', $periodId)->count();
        $filteredRecords = (clone $query)->count();

        if ($request->has('order')) {
            $cols = [0 => 'id', 7 => 'created_at'];
            $idx = $request->order[0]['column'];
            $query->orderBy($cols[$idx] ?? 'created_at', $request->order[0]['dir']);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $skip = $request->start ?? 0;
        $take = ($request->has('length') && $request->length != -1) ? $request->length : 10;
        $returns = $query->skip($skip)->take($take)->get();

        $data = [];
        foreach ($returns as $index => $return) {
            $productsHtml = '';
            foreach (explode(',', $return->product ?? '') as $p) {
                if (trim($p) !== '') {
                    $productsHtml .= '<span class="badge bg-light text-dark border mb-1">' . htmlspecialchars(trim($p)) . '</span><br>';
                }
            }
            if ($productsHtml === '') {
                $productsHtml = 'N/A';
            }

            $data[] = [
                $skip + $index + 1,
                $return->sale->invoice_no ?? 'N/A',
                $productsHtml,
                $return->sale->customer_relation->customer_name ?? 'N/A',
                '<div class="text-center">' . $return->total_items . '</div>',
                '<div class="text-end">' . number_format($return->total_net, 2) . '</div>',
                $return->return_note,
                '<div class="text-center">' . $return->created_at->format('d-m-Y') . '</div>',
                '<div class="text-center"><span class="badge bg-danger">Returned</span></div>',
                '<a href="' . route('saleReturn.invoice', $return->id) . '" target="_blank" class="btn btn-sm btn-info text-white">Receipt</a>',
            ];
        }

        return [
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    }

    private function applySaleOrdering($query, Request $request): void
    {
        if (!$request->has('order')) {
            $query->orderBy('id', 'desc');
            return;
        }
        $columns = [1 => 'invoice_no', 12 => 'total_net', 13 => 'created_at'];
        $col = $columns[$request->order[0]['column']] ?? 'id';
        $query->orderBy($col === 'invoice_no' ? 'id' : $col, $request->order[0]['dir']);
    }

    private function productMapFromSales($sales)
    {
        $ids = [];
        foreach ($sales as $sale) {
            foreach (explode(',', $sale->product ?? '') as $id) {
                $ids[] = trim($id);
            }
        }
        return Product::whereIn('id', array_unique(array_filter($ids)))->get()->keyBy('id');
    }

    private function fmt(): \Closure
    {
        return function ($val) {
            $val = (float) $val;
            return ($val == (int) $val) ? number_format($val, 0) : number_format($val, 2);
        };
    }
}
