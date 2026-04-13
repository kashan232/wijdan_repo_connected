<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomerPayment;
use App\Models\ExpenseVoucher;
use App\Models\Product;
use App\Models\Sale;
use App\Models\VendorPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    public function item_stock_report()
    {
        // Table ke liye paginated
        $products = \App\Models\Product::orderBy('item_name', 'asc')
           ->select('id', 'item_code', 'item_name', 'unit_id')
            ->paginate(50);
       
        // Dropdown ke liye ALL products
        $allProducts = \App\Models\Product::orderBy('item_name', 'asc')
           ->select('id', 'item_code', 'item_name', 'unit_id')
            ->get();

        return view('admin_panel.reporting.item_stock_report',
            compact('products', 'allProducts')
        );
    }

    public function fetchItemStock(Request $request)
    {
        $productId = $request->product_id;
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate   = $request->end_date ?? date('Y-m-t');

        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime   = $endDate . ' 23:59:59';

        $rows = [];
        $grandTotalValue = 0;

        // 🔹 Base query — include only products created within range
        $productsQuery = Product::query();

        if ($productId && $productId !== 'all') {
            $productsQuery->where('id', $productId);
        }

        $perPage = 50;

        $products = $productsQuery
            ->orderBy('item_name')
            ->paginate($perPage);

        foreach ($products as $product) {
            // 🔹 Purchases in date range
            $purchaseData = DB::table('purchase_items')
                ->where('product_id', $product->id)
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->select(
                    DB::raw('COALESCE(SUM(qty),0) as total_qty'),
                    DB::raw('COALESCE(SUM(line_total),0) as total_amount')
                )
                ->first();

            // 🔹 Inward Quantity (from inward_gatepasses)
            $inwardData = DB::table('inward_gatepasses')
                ->join('inward_gatepass_items', 'inward_gatepasses.id', '=', 'inward_gatepass_items.inward_gatepass_id')
                ->where('inward_gatepass_items.product_id', $product->id)
                ->whereBetween('inward_gatepasses.gatepass_date', [$startDate, $endDate])
                ->select(DB::raw('COALESCE(SUM(inward_gatepass_items.qty),0) as total_inward_qty'))
                ->first();

            // 🔹 Sales in date range
            $sold = 0.0;
            $saleAmount = 0.0;
            $sales = DB::table('sales')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->select('product_code', 'qty', 'per_total')
                ->whereNotNull('product_code')
                ->get();

            foreach ($sales as $s) {
                $codes = array_map('trim', explode(',', $s->product_code));
                $qtys  = array_map('trim', explode(',', $s->qty));
                $totals = array_map('trim', explode(',', $s->per_total));

                foreach ($codes as $idx => $code) {
                    if ($code === $product->item_code && isset($qtys[$idx])) {
                        $sold += floatval($qtys[$idx]);
                        $saleAmount += isset($totals[$idx]) ? floatval($totals[$idx]) : 0;
                    }
                }
            }

            // 🔹 Stock balance (latest record if available)
            $stockRecord = DB::table('stocks')
                ->where('product_id', $product->id)
                ->latest('id')
                ->first();

            $balance = $stockRecord ? (float) $stockRecord->qty : 0;

            // 🔹 Calculate stock value
            $stockValue = $balance * (float) ($product->wholesale_price ?? 0);
            $grandTotalValue += $stockValue;

            // 🔹 Purchase Returns in date range
            $purchaseReturnData = DB::table('purchase_return_items')
                ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                ->where('purchase_return_items.product_id', $product->id)
                ->whereBetween('purchase_returns.created_at', [$startDateTime, $endDateTime])
                ->select(
                    DB::raw('COALESCE(SUM(purchase_return_items.qty),0) as total_return_qty'),
                    DB::raw('COALESCE(SUM(purchase_return_items.line_total),0) as total_return_amount')
                )
                ->first();

            // 🔹 Sale Returns in date range
            $saleReturnData = DB::table('sales_returns')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->select('product_code', 'qty', 'per_total')
                ->get();

            $saleReturnQty = 0;
            $saleReturnAmount = 0;

            foreach ($saleReturnData as $sr) {
                $codes = array_map('trim', explode(',', $sr->product_code));
                $qtys  = array_map('trim', explode(',', $sr->qty));
                $totals = array_map('trim', explode(',', $sr->per_total));

                foreach ($codes as $idx => $code) {
                    if ($code === $product->item_code && isset($qtys[$idx])) {
                        $saleReturnQty += floatval($qtys[$idx]);
                        $saleReturnAmount += isset($totals[$idx]) ? floatval($totals[$idx]) : 0;
                    }
                }
            }

            $balance =
                ($product->initial_stock ?? 0)
                + ($inwardData->total_inward_qty ?? 0)
                + ($purchaseData->total_qty ?? 0)
                - ($purchaseReturnData->total_return_qty ?? 0)
                - ($sold ?? 0)
                + ($saleReturnQty ?? 0);

            $rows[] = [
                'id' => $product->id,
                'date' => date('Y-m-d', strtotime($product->created_at)),
                'item_code' => $product->item_code,
                'item_name' => $product->item_name,
                'unit_id' => $product->unit_id,
                'initial_stock' => (float) ($product->initial_stock ?? 0),
                'inward_qty' => (float) ($inwardData->total_inward_qty ?? 0), // 👈 new field
                'purchased' => (float) $purchaseData->total_qty,
                'purchase_return' => (float) $purchaseReturnData->total_return_qty,
                'sold' => (float) $sold,
                'sale_return' => (float) $saleReturnQty,
                'balance' => $balance,
            ];
        }

        return response()->json([
            'data' => $rows,
            'grand_total' => $grandTotalValue,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ]
        ]);
    }



    public function purchase_report()
    {
        return view('admin_panel.reporting.purchase_report');
    }

    public function fetchPurchaseReport(Request $request)
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        /* ================= NORMAL PURCHASE ================= */
        $purchaseQuery = DB::table('purchases')
            ->leftJoin('purchase_items', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->leftJoin('products', 'purchase_items.product_id', '=', 'products.id')
            ->leftJoin('vendors', 'purchases.vendor_id', '=', 'vendors.id')
            ->select(
                DB::raw("'purchase' as source_type"),
                'purchases.purchase_date as purchase_date',
                'purchases.invoice_no',
                'vendors.name as vendor_name',
                'products.item_code',
                'products.item_name',
                'purchase_items.qty',
                'purchase_items.unit',
                'purchase_items.price',
                'purchase_items.item_discount',
                'purchase_items.line_total',
                'purchases.subtotal',
                'purchases.discount',
                'purchases.extra_cost',
                'purchases.net_amount',
                'purchases.paid_amount',
                'purchases.due_amount'
            );

        if ($startDate && $endDate) {
            $purchaseQuery->whereBetween('purchases.purchase_date', [$startDate, $endDate]);
        }

        /* ================= INWARD AS PURCHASE ================= */
        $inwardQuery = DB::table('inward_gatepasses')
            ->leftJoin('inward_gatepass_items', 'inward_gatepasses.id', '=', 'inward_gatepass_items.inward_gatepass_id')
            ->leftJoin('products', 'inward_gatepass_items.product_id', '=', 'products.id')
            ->leftJoin('vendors', 'inward_gatepasses.vendor_id', '=', 'vendors.id')
            ->where('inward_gatepasses.status', 'linked')
            ->where('inward_gatepasses.bill_status', 'billed')
            ->select(
                DB::raw("'inward' as source_type"),
                'inward_gatepasses.gatepass_date as purchase_date',
                'inward_gatepasses.invoice_no',
                'vendors.name as vendor_name',
                'products.item_code',
                'products.item_name',
                'inward_gatepass_items.qty',
                DB::raw('products.unit_id as unit'),
                // Use the specific transaction price from the item table, not master product price
                DB::raw('COALESCE(inward_gatepass_items.price, products.wholesale_price) as price'),
                'inward_gatepass_items.discount_value as item_discount',
                // Calculate line total using the transaction price and subtracting discount
                DB::raw('((COALESCE(inward_gatepass_items.price, products.wholesale_price) - COALESCE(inward_gatepass_items.discount_value, 0)) * inward_gatepass_items.qty) as line_total'),
                'inward_gatepasses.subtotal',
                'inward_gatepasses.discount',
                'inward_gatepasses.extra_cost',
                'inward_gatepasses.net_amount',
                'inward_gatepasses.paid_amount',
                'inward_gatepasses.due_amount'
            );


        if ($startDate && $endDate) {
            $inwardQuery->whereBetween('inward_gatepasses.gatepass_date', [$startDate, $endDate]);
        }

        /* ================= UNION ================= */
        $data = $purchaseQuery
            ->unionAll($inwardQuery)
            ->orderBy('purchase_date', 'desc')
            ->orderBy('invoice_no', 'desc') // Ensure items of same invoice stay together
            ->get();

        // 🔹 Post-processing to remove duplicate invoice totals
        $seenInvoices = [];
        foreach ($data as $row) {
            $uniqueKey = $row->source_type . '_' . $row->invoice_no;

            if (in_array($uniqueKey, $seenInvoices)) {
                // Secondary item: Zero out invoice-level totals to prevent double counting
                $row->subtotal = 0;
                $row->discount = 0;
                $row->extra_cost = 0;
                $row->net_amount = 0;
                $row->paid_amount = 0;
                $row->due_amount = 0;
                $row->is_duplicate = true;
            } else {
                // First item: Keep totals
                $seenInvoices[] = $uniqueKey;
                $row->is_duplicate = false;
            }
        }

        return response()->json([
            'data' => $data
        ]);
    }



    public function sale_report()
    {
        return view('admin_panel.reporting.sale_report');
    }

    public function fetchsaleReport(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start_date;
            $end = $request->end_date;

            $query = DB::table('sales')
                ->leftJoin('customers', 'sales.customer', '=', 'customers.id')
                ->select(
                    'sales.id',
                    'sales.invoice_no', // ✅ Select invoice_no specifically
                    'sales.reference',
                    'sales.product',
                    'sales.product_code',
                    'sales.brand',
                    'sales.unit',
                    'sales.per_price',
                    'sales.per_discount',
                    'sales.qty',
                    'sales.per_total',
                    'sales.total_net',
                    'sales.cash',
                    'sales.card',
                    'sales.change',
                    'sales.created_at',
                    'customers.customer_name',
                    'sales.unit' // Add unit
                );

            if ($start && $end) {
                // Precise Date Filtering (Start 00:00:00 to End 23:59:59)
                $query->whereBetween('sales.created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }

            // Filter by Customer Type/Category
            if ($request->has('customer_type')) {
                $types = $request->customer_type;

                // Ensure types is an array and not empty
                if (is_array($types) && count($types) > 0) {
                    $query->where(function ($q) use ($types) {

                        // 1. Handle Literal "Walk-in Customer" (Not a registered user ID)
                        if (in_array('Walking Customer', $types)) {
                            $q->orWhere('sales.customer', 'Walk-in Customer');
                        }

                        // 2. Filter by Category (Strictly Category, Ignore Type)
                        // This excludes customers like ID 12 who are Wholesalers but have Type='Walking Customer'
                        $q->orWhereIn('customers.customer_category', $types);
                    });
                }
            } else {
                // Default: Show 'Walk-in Customer' literal and 'Walking Customer' category
                $query->where(function ($q) {
                    $q->where('sales.customer', 'Walk-in Customer')
                        ->orWhere('customers.customer_category', 'Walking Customer');
                });
            }

            $sales = $query->orderBy('sales.created_at', 'asc')->get();

            foreach ($sales as $sale) {
                // --- Fetch Product Names using IDs ---
                if (!empty($sale->product)) {
                    $productIds = explode(',', $sale->product);

                    // 1. Fetch all products in one go
                    $productsDict = DB::table('products')
                        ->whereIn('id', $productIds)
                        ->pluck('item_name', 'id'); // [id => name]

                    // 2. Map names back in the EXACT order of $productIds
                    // If a product ID is repeated in the comma-list, its name should also repeat
                    $orderedNames = [];
                    foreach ($productIds as $pid) {
                        $orderedNames[] = $productsDict[$pid] ?? '-';
                    }

                    $sale->product_names = implode(', ', $orderedNames);
                } else {
                    $sale->product_names = '-';
                }

                // --- Merge Sale Returns ---
                $returnsRaw = DB::table('sales_returns')
                    ->where('sale_id', $sale->id)
                    ->get();

                $parsedReturns = [];
                foreach ($returnsRaw as $ret) {
                    $rProducts = explode(',', $ret->product ?? '');
                    $rQtys     = explode(',', $ret->qty ?? '');
                    $rTotals   = explode(',', $ret->per_total ?? ''); // or use per_price * qty if needed

                    foreach ($rProducts as $idx => $rProd) {
                        $q = isset($rQtys[$idx]) ? floatval($rQtys[$idx]) : 0;
                        $t = isset($rTotals[$idx]) ? floatval($rTotals[$idx]) : 0;
                        if ($q > 0) {
                            $parsedReturns[] = [
                                'product' => trim($rProd),
                                'qty'     => $q,
                                'amount'  => $t
                            ];
                        }
                    }
                }
                $sale->returns = $parsedReturns;
            }


            return response()->json($sales);
        }

        return view('admin_panel.reporting.sale_report');
    }

    public function niaz_report()
    {
        return view('admin_panel.reporting.niaz_report');
    }

    public function fetchNiazReport(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start_date;
            $end = $request->end_date;

            $query = DB::table('sales')
                ->leftJoin('customers', 'sales.customer', '=', 'customers.id')
                ->select(
                    'sales.id',
                    'sales.invoice_no',
                    'sales.reference',
                    'sales.product',
                    'sales.product_code',
                    'sales.brand',
                    'sales.unit',
                    'sales.per_price',
                    'sales.per_discount',
                    'sales.qty',
                    'sales.per_total',
                    'sales.total_net',
                    'sales.created_at',
                    'customers.customer_name',
                    'sales.unit'
                );

            if ($start && $end) {
                $query->whereBetween('sales.created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }

            if ($request->has('customer_type')) {
                $types = $request->customer_type;
                if (is_array($types) && count($types) > 0) {
                    $query->where(function ($q) use ($types) {
                        if (in_array('Walking Customer', $types)) {
                            $q->orWhere('sales.customer', 'Walk-in Customer');
                        }
                        $q->orWhereIn('customers.customer_category', $types);
                    });
                }
            } else {
                $query->where(function ($q) {
                    $q->where('sales.customer', 'Walk-in Customer')
                        ->orWhere('customers.customer_category', 'Walking Customer');
                });
            }

            $sales = $query->orderBy('sales.created_at', 'asc')->get();

            foreach ($sales as $sale) {
                // Store original totals for UI display
                $sale->original_per_total = $sale->per_total;
                $sale->original_total_net = $sale->total_net;

                // Apply 4% calculation to totals
                if ($sale->per_total) {
                    $totals = explode(',', $sale->per_total);
                    $newTotals = array_map(function($val) {
                        return floatval($val) * 0.04;
                    }, $totals);
                    $sale->niaz_per_total = implode(',', $newTotals);
                } else {
                    $sale->niaz_per_total = '0';
                }
                
                if ($sale->total_net) {
                    $sale->niaz_total_net = floatval($sale->total_net) * 0.04;
                } else {
                    $sale->niaz_total_net = 0;
                }

                // --- Fetch Product Names using IDs ---
                if (!empty($sale->product)) {
                    $productIds = explode(',', $sale->product);
                    $productsDict = DB::table('products')
                        ->whereIn('id', $productIds)
                        ->pluck('item_name', 'id');
                    $orderedNames = [];
                    foreach ($productIds as $pid) {
                        $orderedNames[] = $productsDict[$pid] ?? '-';
                    }
                    $sale->product_names = implode(', ', $orderedNames);
                } else {
                    $sale->product_names = '-';
                }

                // --- Merge Sale Returns ---
                $returnsRaw = DB::table('sales_returns')
                    ->where('sale_id', $sale->id)
                    ->get();

                $parsedReturns = [];
                foreach ($returnsRaw as $ret) {
                    $rProducts = explode(',', $ret->product ?? '');
                    $rQtys     = explode(',', $ret->qty ?? '');
                    $rTotals   = explode(',', $ret->per_total ?? '');

                    foreach ($rProducts as $idx => $rProd) {
                        $q = isset($rQtys[$idx]) ? floatval($rQtys[$idx]) : 0;
                        $t = isset($rTotals[$idx]) ? floatval($rTotals[$idx]) : 0;
                        if ($q > 0) {
                            $parsedReturns[] = [
                                'product' => trim($rProd),
                                'qty'     => $q,
                                'original_amount' => $t,
                                'niaz_amount'  => floatval($t) * 0.04
                            ];
                        }
                    }
                }
                $sale->returns = $parsedReturns;
            }

            return response()->json($sales);
        }

        return view('admin_panel.reporting.niaz_report');
    }

    public function sale_bonus_report()
    {
        return view('admin_panel.reporting.sale_bonus_report');
    }

    public function fetchSaleBonusReport(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start_date;
            $end = $request->end_date;

            $query = DB::table('sales')
                ->leftJoin('customers', 'sales.customer', '=', 'customers.id')
                ->select(
                    'sales.id',
                    'sales.invoice_no',
                    'sales.reference',
                    'sales.product',
                    'sales.product_code',
                    'sales.unit',
                    'sales.qty',
                    'sales.created_at',
                    'customers.customer_name'
                );

            if ($start && $end) {
                $query->whereBetween('sales.created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }

            if ($request->has('customer_type')) {
                $types = $request->customer_type;
                if (is_array($types) && count($types) > 0) {
                    $query->where(function ($q) use ($types) {
                        if (in_array('Walking Customer', $types)) {
                            $q->orWhere('sales.customer', 'Walk-in Customer');
                        }
                        $q->orWhereIn('customers.customer_category', $types);
                    });
                }
            } else {
                $query->where(function ($q) {
                    $q->where('sales.customer', 'Walk-in Customer')
                        ->orWhere('customers.customer_category', 'Walking Customer');
                });
            }

            $sales = $query->orderBy('sales.created_at', 'asc')->get();

            foreach ($sales as $sale) {
                // --- Calculate Pieces Sold ---
                $qtyArr = explode(',', $sale->qty ?? '');
                $unitArr = explode(',', $sale->unit ?? '');
                
                $rowPieces = 0;
                foreach ($qtyArr as $idx => $qVal) {
                    $q = (float) trim($qVal);
                    $u = strtolower(trim($unitArr[$idx] ?? 'piece'));
                    
                    if (str_contains($u, 'meter')) {
                        $rowPieces += $q / 4.5;
                    } elseif (str_contains($u, 'yard')) {
                        $rowPieces += $q / 8;
                    } else {
                        // All others treated as pieces (including piece/pcs/set etc)
                        $rowPieces += $q;
                    }
                }
                $sale->pieces_sold = $rowPieces;

                // --- Fetch Product Names ---
                if (!empty($sale->product)) {
                    $pDict = DB::table('products')->whereIn('id', explode(',', $sale->product))->pluck('item_name', 'id');
                    $sale->product_names = implode(', ', array_map(fn($pid) => $pDict[$pid] ?? '-', explode(',', $sale->product)));
                } else {
                    $sale->product_names = '-';
                }

                // --- Calculate Returns (Net Pieces) ---
                $returnsRaw = DB::table('sales_returns')->where('sale_id', $sale->id)->get();
                $rowReturnPieces = 0;
                foreach ($returnsRaw as $ret) {
                    $rQtys = explode(',', $ret->qty ?? '');
                    $rUnits = explode(',', $ret->unit ?? '');
                    
                    foreach ($rQtys as $rIdx => $rQVal) {
                        $rq = (float) trim($rQVal);
                        $ru = strtolower(trim($rUnits[$rIdx] ?? 'piece'));
                        
                        if (str_contains($ru, 'meter')) {
                            $rowReturnPieces += $rq / 4.5;
                        } elseif (str_contains($ru, 'yard')) {
                            $rowReturnPieces += $rq / 8;
                        } else {
                            $rowReturnPieces += $rq;
                        }
                    }
                }
                $sale->return_pieces = $rowReturnPieces;
                $sale->net_pieces = $sale->pieces_sold - $sale->return_pieces;
                $sale->bonus_amount = $sale->net_pieces * 8;
            }

            return response()->json($sales);
        }

        return view('admin_panel.reporting.sale_bonus_report');
    }



    public function sale_report_category()
    {
        $categories = Category::select('id', 'name')->get();
        return view('admin_panel.reporting.sale_report_category', compact('categories'));
    }

    public function fetchsalecategoryReport(Request $request)
    {
        if ($request->ajax()) {

            $start      = $request->start_date;
            $end        = $request->end_date;
            $categoryId = $request->category_id;
            $subCategoryId = $request->subcategory_id; // Get subcategory ID

            // ================== BASE SALES QUERY ==================
            $query = DB::table('sales')
                ->leftJoin('customers', 'sales.customer', '=', 'customers.id')
                ->select(
                    'sales.id',
                    'sales.invoice_no',
                    'sales.reference',
                    'sales.product',
                    'sales.product_code',
                    'sales.brand',
                    'sales.unit',
                    'sales.per_price',
                    'sales.per_discount',
                    'sales.qty',
                    'sales.per_total',
                    'sales.total_net',
                    'sales.created_at',
                    'customers.customer_name'
                )
                ->when($start && $end, function ($q) use ($start, $end) {
                    $q->whereBetween('sales.created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
                });

            // ================== CUSTOMER FILTERING ==================
            if ($request->has('customer_type')) {
                $types = $request->customer_type;
                if (is_array($types) && count($types) > 0) {
                    $query->where(function ($q) use ($types) {

                        // 1. Literal "Walk-in Customer"
                        if (in_array('Walking Customer', $types)) {
                            $q->orWhere('sales.customer', 'Walk-in Customer');
                        }
                        // 2. Category Match
                        $q->orWhereIn('customers.customer_category', $types);
                    });
                }
            } else {
                // Default: Walking
                $query->where(function ($q) {
                    $q->where('sales.customer', 'Walk-in Customer')
                        ->orWhere('customers.customer_category', 'Walking Customer');
                });
            }

            $sales = $query->orderBy('sales.created_at', 'asc')->get();

            $finalSales = [];

            // ================== LOOP SALES ==================
            foreach ($sales as $sale) {

                if (empty($sale->product)) {
                    continue;
                }

                // Convert CSV → Arrays
                $productIds = explode(',', $sale->product);
                $qtyArr     = explode(',', $sale->qty);
                $priceArr   = explode(',', $sale->per_price);
                $totalArr   = explode(',', $sale->per_total);
                $unitArr    = explode(',', $sale->unit);

                // ================== PRODUCTS QUERY ==================
                // Get all products matching filter for THIS sale
                $products = DB::table('products')
                    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                    ->leftJoin('subcategories', 'products.sub_category_id', '=', 'subcategories.id')
                    ->whereIn('products.id', $productIds)
                    ->when($categoryId, function ($q) use ($categoryId) {
                        $q->where('products.category_id', $categoryId);
                    })
                    ->when($subCategoryId, function ($q) use ($subCategoryId) {
                        $q->where('products.sub_category_id', $subCategoryId);
                    })
                    ->select(
                        'products.id',
                        'products.item_name',
                        'products.item_code',
                        'categories.name as category_name',
                        'subcategories.name as subcategory_name'
                    )
                    ->get();

                // Skip if no product matched category/subcategory
                if ($products->isEmpty()) {
                    continue;
                }

                $matchingPids  = $products->pluck('id')->toArray();
                $matchingCodes = $products->pluck('item_code')->toArray();

                // ================== MATCH VALUES (Handle Multiple Occurrences) ==================
                $matchedQty   = [];
                $matchedPrice = [];
                $matchedTotal = [];
                $matchedUnit  = [];

                foreach ($productIds as $idx => $pid) {
                    $pid = trim($pid);
                    if (in_array($pid, $matchingPids)) {
                        $matchedQty[]   = (float) ($qtyArr[$idx] ?? 0);
                        $matchedPrice[] = (float) ($priceArr[$idx] ?? 0);
                        $matchedTotal[] = (float) ($totalArr[$idx] ?? 0);
                        $matchedUnit[]  = trim($unitArr[$idx] ?? '-');
                    }
                }

                // ================== ASSIGN FILTERED DATA ==================
                $sale->product_names  = $products->pluck('item_name')->implode(', ');
                $sale->categories     = $products->pluck('category_name')->implode(', ');
                $sale->subcategories  = $products->pluck('subcategory_name')->implode(', ');

                $sale->filtered_qty   = implode(', ', $matchedQty);
                $sale->filtered_price = implode(', ', $matchedPrice);
                $sale->filtered_total = implode(', ', $matchedTotal);
                $sale->filtered_unit  = implode(', ', $matchedUnit);

                $sale->filtered_net   = array_sum($matchedTotal);

                // ================== SALE RETURNS (Filtered by matching items) ==================
                $returnsRaw = DB::table('sales_returns')
                    ->where('sale_id', $sale->id)
                    ->get();

                $parsedReturns = [];
                foreach ($returnsRaw as $ret) {
                    $rNames  = explode(',', $ret->product ?? '');
                    $rCodes  = explode(',', $ret->product_code ?? '');
                    $rQtys   = explode(',', $ret->qty ?? '');
                    $rTotals = explode(',', $ret->per_total ?? '');

                    foreach ($rCodes as $idx => $code) {
                        $code = trim($code);
                        if (in_array($code, $matchingCodes)) {
                            $q = isset($rQtys[$idx]) ? floatval($rQtys[$idx]) : 0;
                            $t = isset($rTotals[$idx]) ? floatval($rTotals[$idx]) : 0;
                            $n = isset($rNames[$idx]) ? trim($rNames[$idx]) : 'Unknown';

                            if ($q > 0) {
                                $parsedReturns[] = [
                                    'product'   => $n,
                                    'qty'       => $q,
                                    'per_total' => $t
                                ];
                            }
                        }
                    }
                }
                $sale->returns = $parsedReturns;

                $finalSales[] = $sale;
            }

            return response()->json($finalSales);
        }

        return view('admin_panel.reporting.sale_report_category'); // Should not be reached if handled by first method, but keeping safe
    }



    public function customer_ledger_report()
    {
        $customers = DB::table('customers')->select('id', 'customer_name')->get();

        return view('admin_panel.reporting.customer_ledger_report', compact('customers'));
    }

    public function fetch_customer_ledger(Request $request)
    {
        $customerId = $request->customer_id;
        $start = $request->start_date;
        $end = $request->end_date . ' 23:59:59';

        // Customer info
        $customer = DB::table('customers')->where('id', $customerId)->first();

        // ---------------- CALCULATE OPENING BALANCE DYNAMICALLY ----------------
        // 1. Initial Opening from Customer
        $initial = $customer->opening_balance ?? 0;

        // 2. Prior Sales (Net of Returns as stored in DB)
        $prevSales = DB::table('sales')
            ->where('customer', $customerId)
            ->where('created_at', '<', $start)
            ->sum(DB::raw('COALESCE(total_net, total_bill_amount)'));

        // 3. Prior Payments
        $prevPayments = DB::table('customer_payments')
            ->where('customer_id', $customerId)
            ->where('payment_date', '<', $start)
            ->sum('amount');

        // 4. Prior Charges (New)
        $prevCharges = DB::table('customer_charges')
            ->where('customer_id', $customerId)
            ->where('date', '<', $start)
            ->sum('amount');

        // 5. Returns that happened ON/AFTER StartDate, but belong to Prior Sales.
        // These need to be added back because the Prior Sales Sum is already reduced by them,
        // but the credit event hasn't happened yet in this timeline.
        $addBackReturns = DB::table('sales_returns')
            ->join('sales', 'sales_returns.sale_id', '=', 'sales.id')
            ->where('sales_returns.customer', $customerId)
            ->where('sales_returns.created_at', '>=', $start)
            ->where('sales.created_at', '<', $start)
            ->sum('sales_returns.total_net');

        $opening = $initial + $prevSales + $prevCharges - $prevPayments + $addBackReturns;

        // ---------------- FETCH ALL SALE RETURNS FIRST ----------------
        $allSaleReturns = DB::table('sales_returns')
            ->where('customer', $customerId)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy('sale_id'); // group by sale_id for easy lookup

        // ---------------- SALES (Debit) ----------------
        $sales = DB::table('sales')
            ->where('customer', $customerId)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function ($s) use ($allSaleReturns) {
                $fullSaleAmount = $s->total_net ?? $s->total_bill_amount;

                // Check if this sale has any returns
                $returnTotal = 0;
                if (isset($allSaleReturns[$s->id])) {
                    $returnTotal = $allSaleReturns[$s->id]->sum('total_net');
                }

                // Sale debit = original sale + total of related returns
                $debitAmount = $fullSaleAmount + $returnTotal;

                return [
                    'date' => $s->created_at,
                    'sort_type' => 1,
                    'invoice' => $s->invoice_no,
                    'reference' => $s->reference,
                    'description' => 'To Sale A/c',
                    'debit' => $debitAmount,
                    'credit' => 0,
                    'original_sale_id' => $s->id
                ];
            });

        // ---------------- CHARGES (Debit) ----------------
        $charges = DB::table('customer_charges')
            ->where('customer_id', $customerId)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->map(function ($c) {
                return [
                    'date' => $c->date . ' 23:59:59',
                    'sort_type' => 4,
                    'invoice' => $c->charge_no,
                    'reference' => $c->transporter_name,
                    'description' => $c->note ?? 'To Charge / Expense A/c',
                    'debit' => (float) $c->amount,
                    'credit' => 0,
                ];
            });

        // ---------------- SALE RETURNS (Credit) ----------------
        $saleReturns = collect();
        foreach ($allSaleReturns as $saleId => $returns) {
            foreach ($returns as $r) {
                $saleReturns->push([
                    'date' => $r->created_at,
                    'sort_type' => 3,
                    'invoice' => 'SR-' . $r->sale_id,
                    'reference' => $r->reference,
                    'description' => 'By Sale Return',
                    'debit' => 0,
                    'credit' => $r->total_net,
                    'original_sale_id' => $r->sale_id
                ]);
            }
        }

        // ---------------- PAYMENTS (Credit) ----------------
        $payments = DB::table('customer_payments')
            ->where('customer_id', $customerId)
            ->whereBetween('payment_date', [$start, $end])
            ->get()
            ->map(function ($p) {
                return [
                    'date' => $p->payment_date . ' 23:59:59',
                    'sort_type' => 2,
                    'invoice' => $p->received_no,
                    'reference' => $p->payment_method,
                    'description' => $p->note ?? 'Payment Received',
                    'debit' => 0,
                    'credit' => $p->amount,
                ];
            });

        // ---------------- MERGE + SORT ----------------
        $transactions = collect()
            ->merge($sales)
            ->merge($charges)
            ->merge($saleReturns)
            ->merge($payments)
            ->sort(function ($a, $b) {
                $dateA = strtotime($a['date']);
                $dateB = strtotime($b['date']);
                if ($dateA != $dateB) return $dateA <=> $dateB;

                // Sale → Sale Return → Payment → Charge
                $order = [1 => 1, 3 => 2, 2 => 3, 4 => 4];
                return $order[$a['sort_type']] <=> $order[$b['sort_type']];
            })
            ->values()
            ->all();

        // ---------------- RUNNING BALANCE ----------------
        $balance = $opening;
        foreach ($transactions as $key => $t) {
            $balance += $t['debit'];
            $balance -= $t['credit'];
            $transactions[$key]['balance'] = $balance;
        }

        return response()->json([
            'customer' => $customer,
            'opening_balance' => $opening,
            'transactions' => $transactions,
        ]);
    }





    public function vendor_ledger_report()
    {
        $vendors = DB::table('vendors')->select('id', 'name')->get();

        return view('admin_panel.reporting.vendor_ledger_report', compact('vendors'));
    }

    public function fetch_vendor_ledger(Request $request)
    {
        $vendorId = $request->vendor_id;
        $start = $request->start_date;
        $end = $request->end_date . ' 23:59:59';

        $vendor = DB::table('vendors')->where('id', $vendorId)->first();
        // ---------------- CALCULATE OPENING BALANCE DYNAMICALLY ----------------
        // 1. Initial Opening from Vendor
        $initial = $vendor->opening_balance ?? 0;

        // 2. Prior Purchases (Debit: We owe more)
        $prevPurchases = DB::table('purchases')
            ->where('vendor_id', $vendorId)
            ->where('purchase_date', '<', $start)
            ->sum('net_amount');

        // 2b. Prior Inwards (Debit: We owe more)
        $prevInwards = DB::table('inward_gatepasses')
            ->where('vendor_id', $vendorId)
            ->where('bill_status', 'billed')
            ->where('gatepass_date', '<', $start)
            ->sum('net_amount');

        // 2c. Prior Bilties (Debit: We owe more)
        $prevBilties = DB::table('vendor_bilties')
            ->where('vendor_id', $vendorId)
            ->where('delivery_date', '<', $start)
            ->sum('amount');

        // 3. Prior Returns (Credit: We owe less)
        $prevReturns = DB::table('purchase_returns')
            ->where('vendor_id', $vendorId)
            ->where('return_date', '<', $start)
            ->sum('net_amount');

        // 4. Prior Payments (Credit: We owe less)
        $prevPayments = DB::table('vendor_payments')
            ->where('vendor_id', $vendorId)
            ->where('payment_date', '<', $start)
            ->sum('amount');

        $opening = $initial + $prevPurchases + $prevInwards + $prevBilties - $prevReturns - $prevPayments;

        // 🔹 1. Purchases → Debit (we owe vendor)
        $purchases = DB::table('purchases')
            ->where('vendor_id', $vendorId)
            ->whereBetween('purchase_date', [$start, $end])
            ->select('purchase_date', 'invoice_no', 'net_amount', 'note') // Explicitly select note
            ->get()
            ->map(function ($p) {
                return [
                    'date' => $p->purchase_date,
                    'invoice' => $p->invoice_no,
                    'description' => $p->note ?: 'Purchase Invoice', // Use note if available
                    'debit' => $p->net_amount,
                    'credit' => 0,
                    'sort_date' => $p->purchase_date
                ];
            });

        // 🔹 1b. Inward Bills → Debit (we owe vendor)
        $inwards = DB::table('inward_gatepasses')
            ->where('vendor_id', $vendorId)
            ->where('bill_status', 'billed')
            ->whereBetween('gatepass_date', [$start, $end])
            ->get()
            ->map(function ($i) {
                return [
                    'date' => $i->gatepass_date,
                    'invoice' => $i->invoice_no . ' (' . $i->gatepass_no . ')',
                    'description' => 'Inward Bill - ' . ($i->remarks ?? ''),
                    'debit' => $i->net_amount,
                    'credit' => 0,
                    'sort_date' => $i->gatepass_date
                ];
            });

        // 🔹 1c. Bilties → Debit (we owe vendor)
        $bilties = DB::table('vendor_bilties')
            ->where('vendor_id', $vendorId)
            ->whereBetween('delivery_date', [$start, $end])
            ->get()
            ->map(function ($b) {
                return [
                    'date' => $b->delivery_date,
                    'invoice' => $b->bilty_no,
                    'description' => 'Bilty - ' . ($b->transporter_name ?? '') . ' (' . ($b->vehicle_no ?? '') . ')',
                    'debit' => $b->amount,
                    'credit' => 0,
                    'sort_date' => $b->delivery_date
                ];
            });

        // 🔹 2. Purchase Returns → Credit (reduces vendor balance)
        $returns = DB::table('purchase_returns')
            ->where('vendor_id', $vendorId)
            ->whereBetween('return_date', [$start, $end])
            ->get()
            ->map(function ($r) {
                return [
                    'date' => $r->return_date,
                    'invoice' => $r->return_invoice,
                    'description' => 'Purchase Return',
                    'debit' => 0,
                    'credit' => $r->net_amount,
                    'sort_date' => $r->return_date
                ];
            });

        // 🔹 3. Vendor Payments → Credit (we paid vendor)
        $payments = DB::table('vendor_payments')
            ->where('vendor_id', $vendorId)
            ->whereBetween('payment_date', [$start, $end])
            ->get()
            ->map(function ($v) {
                return [
                    'date' => $v->payment_date,
                    'invoice' => $v->payment_no,
                    'reference' => $v->payment_method,
                    'description' => $v->note ?? 'Cash Given',
                    'debit' => 0,
                    'credit' => $v->amount,
                    'sort_date' => $v->payment_date
                ];
            });

        // 🔹 Merge all
        $transactions = $purchases
            ->merge($inwards)
            ->merge($bilties)
            ->merge($returns)
            ->merge($payments)
            ->sortBy('sort_date')
            ->values()
            ->all();

        // 🔹 Running Balance Calculation (Debit increases, Credit decreases)
        $balance = $opening;

        foreach ($transactions as $key => $t) {
            $debit  = (float) ($t['debit'] ?? 0);
            $credit = (float) ($t['credit'] ?? 0);

            $balance = $balance + $debit - $credit;
            $transactions[$key]['balance'] = round($balance, 2);
        }

        return response()->json([
            'vendor' => $vendor,
            'opening_balance' => $opening,
            'transactions' => $transactions,
        ]);
    }

    public function cashbook(Request $request)
    {
        // ✅ Allow date filtering, default to today
        $selectedDate = $request->get('date', Carbon::today()->toDateString());
        $today = $selectedDate;

        // ✅ Cashbook Start Date (transactions before this are ignored)
        // Default: 30 days ago to avoid huge opening balance from old data
        $startDate = $request->get('start_date', Carbon::today()->subDays(30)->toDateString());

        /* ================= CALCULATE OPENING BALANCE ================= */
        // Opening = sum of ALL transactions BETWEEN start_date and selected date (exclusive)

        // Sales after start_date but before selected date
        $previousSales = Sale::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<', $today)
            ->sum('total_net');

        // Customer recoveries after start_date but before selected date
        $previousCustomerRecoveries = CustomerPayment::whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<', $today)
            ->sum('amount');

        // Vendor payments after start_date but before selected date
        $previousVendorPayments = VendorPayment::whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<', $today)
            ->sum('amount');

        // Expenses after start_date but before selected date
        $previousExpenses = ExpenseVoucher::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<', $today)
            ->sum('total_amount');

        // Opening = Previous Receipts - Previous Payments
        $openingBalance = ($previousSales + $previousCustomerRecoveries) - ($previousVendorPayments + $previousExpenses);

        /* ================= RECEIPTS (Current Date) ================= */

        $receipts = [];

        // ✅ ALL Sales (not just walk-in)
        $allSales = Sale::whereDate('created_at', $today)->get();

        foreach ($allSales as $sale) {
            $receipts[] = [
                'title'  => 'Sale',
                'ref'    => 'Invoice #' . $sale->invoice_no,
                'amount' => $sale->total_net,
            ];
        }

        // ✅ ALL Customer Recoveries (not just cash)
        $customerRecoveries = CustomerPayment::with('customer')
            ->whereDate('payment_date', $today)
            ->get();

        foreach ($customerRecoveries as $pay) {
            $receipts[] = [
                'title'  => 'Customer Recovery',
                'ref'    => ($pay->customer->customer_name ?? '-') . ' (' . ($pay->payment_method ?? 'N/A') . ')',
                'amount' => $pay->amount,
            ];
        }

        $totalReceipts = collect($receipts)->sum('amount');

        /* ================= PAYMENTS (Current Date) ================= */

        $payments = [];

        // ✅ ALL Vendor Payments (not just cash)
        $vendorPayments = VendorPayment::with('vendor')
            ->whereDate('payment_date', $today)
            ->get();

        foreach ($vendorPayments as $pay) {
            $payments[] = [
                'title'  => 'Vendor Payment',
                'ref'    => ($pay->vendor->name ?? '-') . ' (' . ($pay->payment_method ?? 'N/A') . ')',
                'amount' => $pay->amount,
            ];
        }

        // ✅ ALL Expense Vouchers
        $expenseVouchers = ExpenseVoucher::whereDate('date', $today)->get();

        foreach ($expenseVouchers as $exp) {
            $remarks = is_array($exp->remarks) ? implode(', ', $exp->remarks) : ($exp->remarks ?? '');
            $payments[] = [
                'title'  => 'Expense',
                'ref'    => $remarks ?: 'Voucher #' . $exp->evid,
                'amount' => $exp->total_amount,
            ];
        }

        $totalPayments = collect($payments)->sum('amount');

        /* ================= CLOSING BALANCE ================= */

        $closingBalance = $openingBalance + $totalReceipts - $totalPayments;

        // IMPORTANT for blade loop
        $maxRows = max(count($receipts), count($payments));

        return view('admin_panel.reporting.CashBook', compact(
            'receipts',
            'payments',
            'maxRows',
            'totalReceipts',
            'totalPayments',
            'openingBalance',
            'closingBalance',
            'selectedDate',
            'startDate'
        ));
    }




    public function expense_vocher(Request $request)
    {
        $accountHeads = \App\Models\AccountHead::where('status', 1)->get();
        $accounts     = \App\Models\Account::where('status', 1)->get();

        $vouchers = collect();
        $grandTotal = 0;

        if ($request->hasAny(['account_heads', 'accounts', 'start_date', 'end_date'])) {

            $query = \App\Models\ExpenseVoucher::query();

            // Account Head filter (type = account_head_id)
            if ($request->filled('account_heads') && !in_array('all', $request->account_heads)) {
                $query->whereIn('type', $request->account_heads);
            }

            // Account filter (party_id = account_id)
            if ($request->filled('accounts')) {
                $query->whereIn('party_id', $request->accounts);
            }

            // Date filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $vouchers = $query->latest()->get();

            // Grand Total
            $grandTotal = $vouchers->sum('total_amount');
        }

        return view(
            'admin_panel.reporting.expense_vocher',
            compact('accountHeads', 'accounts', 'vouchers', 'grandTotal')
        );
    }

    public function expenseVoucherAjax(Request $request)
    {
        $query = \App\Models\ExpenseVoucher::query();

        // Account Head (type)
        if ($request->filled('account_heads') && !in_array('all', $request->account_heads)) {
            $query->whereIn('type', $request->account_heads);
        }

        // Accounts (party_id)
        if ($request->filled('accounts')) {
            $query->whereIn('party_id', $request->accounts);
        }

        // Date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $vouchers = $query->latest()->get();

        $data = $vouchers->map(function ($v) {

            // remarks decode (JSON safe)
            $remarks = json_decode($v->remarks, true);

            return [
                'evid'    => $v->evid,
                'date'    => \Carbon\Carbon::parse($v->date)->format('d-m-Y'),
                'head'    => optional(\App\Models\AccountHead::find($v->type))->name,
                'account' => optional(\App\Models\Account::find($v->party_id))->title,
                'remarks' => is_array($remarks) ? implode(', ', $remarks) : ($v->remarks ?? '-'),
                'amount'  => number_format($v->total_amount, 2),
            ];
        });

        return response()->json([
            'rows' => $data,
            'total' => number_format($vouchers->sum('total_amount'), 2)
        ]);
    }
}
