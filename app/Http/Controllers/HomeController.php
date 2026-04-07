<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $usertype = Auth::user()->usertype;
        $userId = Auth::id();

        if ($usertype == 'user') {
            return view('user_panel.dashboard', compact('userId'));
        } elseif ($usertype == 'admin') {

            return view('admin_panel.dashboard');
        } else {
            return redirect()->back()->with('error', 'Unauthorized access');
        }
    }


    public function System_Reports(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $categoryCount = DB::table('categories')->count();
        $subcategoryCount = DB::table('subcategories')->count();
        $productCount = DB::table('products')->count();
        $customerscount = DB::table('customers')->count();

        // Defaults (0 / Empty)
        $totalPurchases = 0;
        $totalPurchaseReturns = 0;
        $totalSales = 0;
        $totalSalesReturns = 0;
        $totalExpenses = 0;
        $totalCashSales = 0;
        $totalCardSales = 0;
        
        $salesChartStats = [
            'daily' => ['categories' => [], 'series' => []],
            'weekly' => ['categories' => [], 'series' => []],
            'monthly' => ['categories' => [], 'series' => []]
        ];
        $purchaseChartStats = [
            'daily' => ['categories' => [], 'series' => []],
            'weekly' => ['categories' => [], 'series' => []],
            'monthly' => ['categories' => [], 'series' => []]
        ];

        $expenseTrendChartStats = [
            'daily' => ['categories' => [], 'series' => []],
            'weekly' => ['categories' => [], 'series' => []],
            'monthly' => ['categories' => [], 'series' => []]
        ];

        // Apply Logic ONLY if Filter Exists
        if ($startDate && $endDate) {
            $startObj = Carbon::parse($startDate)->startOfMonth();
            $endObj   = Carbon::parse($endDate)->endOfMonth();

            $start = $startObj->format('Y-m-d 00:00:00');
            $end   = $endObj->format('Y-m-d 23:59:59');

            // --- TOTALS ---
            $purchasesQuery = DB::table('purchases')
                ->whereBetween('purchase_date', [$startObj->format('Y-m-d'), $endObj->format('Y-m-d')]);
            
            $purchaseReturnsQuery = DB::table('purchase_returns')
                ->whereBetween('return_date', [$startObj->format('Y-m-d'), $endObj->format('Y-m-d')]);

            $salesQuery = DB::table('sales')
                ->leftJoin('customers', 'sales.customer', '=', 'customers.id')
                ->whereBetween('sales.created_at', [$start, $end])
                ->where(function ($q) {
                    $q->where('sales.customer', 'Walk-in Customer')
                        ->orWhere('customers.customer_category', 'Walking Customer')
                        ->orWhere('customers.customer_category', 'Retailer');
                });

            $salesReturnsQuery = DB::table('sales_returns')
                ->leftJoin('customers', 'sales_returns.customer', '=', 'customers.id')
                ->whereBetween('sales_returns.created_at', [$start, $end])
                ->where(function ($q) {
                    $q->where('sales_returns.customer', 'Walk-in Customer')
                        ->orWhere('customers.customer_category', 'Walking Customer')
                        ->orWhere('customers.customer_category', 'Retailer');
                });

            $totalPurchases = $purchasesQuery->sum('net_amount');
            $totalPurchaseReturns = $purchaseReturnsQuery->sum('net_amount');
            $totalSales = $salesQuery->sum('sales.total_net');
            $totalCashSales = $salesQuery->sum(DB::raw('sales.cash - sales.change'));
            $totalCardSales = $salesQuery->sum('sales.card');
            $totalSalesReturns = $salesReturnsQuery->sum('sales_returns.total_net');

            $totalExpenses = DB::table('expense_vouchers')
                ->whereBetween('date', [$startObj->format('Y-m-d'), $endObj->format('Y-m-d')])
                ->sum('total_amount');

            // --- CHARTS ---
            $chartStart = $startObj;
            $chartEnd   = $endObj;
            $diffInDays = $chartStart->diffInDays($chartEnd);
            
            $granularity = 'daily';
            if ($diffInDays > 90) {
                $granularity = 'monthly';
            } elseif ($diffInDays > 14) {
                $granularity = 'weekly';
            }

            // Helpers
            $getSalesData = function($selectRaw) use ($start, $end) {
                return DB::table('sales')
                    ->leftJoin('customers', 'sales.customer', '=', 'customers.id')
                    ->whereBetween('sales.created_at', [$start, $end])
                    ->where(function ($sub) {
                        $sub->where('sales.customer', 'Walk-in Customer')
                            ->orWhere('customers.customer_category', 'Walking Customer')
                            ->orWhere('customers.customer_category', 'Retailer');
                    })
                    ->select(DB::raw("$selectRaw as label_key"), DB::raw('SUM(sales.total_net) as total'))
                    ->groupBy('label_key')->orderBy('label_key')->pluck('total', 'label_key');
            };

            $getPurchaseData = function($selectRaw) use ($startObj, $endObj) {
                 return DB::table('purchases')
                    ->whereBetween('purchase_date', [$startObj->format('Y-m-d'), $endObj->format('Y-m-d')])
                    ->select(DB::raw("$selectRaw as label_key"), DB::raw('SUM(net_amount) as total'))
                    ->groupBy('label_key')->orderBy('label_key')->pluck('total', 'label_key');
            };

            $getExpenseDataTrend = function($selectRaw) use ($start, $end) {
                return DB::table('expense_vouchers')
                   ->whereBetween('date', [substr($start, 0, 10), substr($end, 0, 10)])
                   ->select(DB::raw("$selectRaw as label_key"), DB::raw('SUM(total_amount) as total'))
                   ->groupBy('label_key')->orderBy('label_key')->pluck('total', 'label_key');
           };

            // Build Data
            $labels = [];
            $salesSeriesData = [];
            $purchaseSeriesData = [];
            $expenseSeriesDataTrend = [];

            if ($granularity === 'daily') {
                $period = \Carbon\CarbonPeriod::create($chartStart, $chartEnd);
                $salesMap = $getSalesData('DATE(sales.created_at)'); 
                $purchaseMap = $getPurchaseData('DATE(purchase_date)');
                $expenseMap = $getExpenseDataTrend('DATE(date)');

                foreach ($period as $dt) {
                    $key = $dt->format('Y-m-d');
                    $labels[] = $dt->format('d M (D)');
                    $salesSeriesData[] = $salesMap[$key] ?? 0;
                    $purchaseSeriesData[] = $purchaseMap[$key] ?? 0;
                    $expenseSeriesDataTrend[] = $expenseMap[$key] ?? 0;
                }
            } elseif ($granularity === 'weekly') {
                $salesMap = $getSalesData("YEARWEEK(sales.created_at, 1)");
                $purchaseMap = $getPurchaseData("YEARWEEK(purchase_date, 1)");
                $expenseMap = $getExpenseDataTrend("YEARWEEK(date, 1)");

                $current = $chartStart->copy()->startOfWeek();
                $endWeek = $chartEnd->copy()->endOfWeek();
                while ($current <= $endWeek) {
                    $dbKey = $current->format('oW');
                    $labels[] = "Week " . $current->weekOfYear . " - " . $current->format('M Y');
                    $salesSeriesData[] = $salesMap[$dbKey] ?? 0;
                    $purchaseSeriesData[] = $purchaseMap[$dbKey] ?? 0;
                    $expenseSeriesDataTrend[] = $expenseMap[$dbKey] ?? 0;
                    $current->addWeek();
                }
            } elseif ($granularity === 'monthly') {
                $salesMap = $getSalesData("DATE_FORMAT(sales.created_at, '%Y-%m')");
                $purchaseMap = $getPurchaseData("DATE_FORMAT(purchase_date, '%Y-%m')");
                $expenseMap = $getExpenseDataTrend("DATE_FORMAT(date, '%Y-%m')");

                $current = $chartStart->copy()->startOfMonth();
                $endMonth = $chartEnd->copy()->endOfMonth();
                while ($current <= $endMonth) {
                    $key = $current->format('Y-m');
                    $labels[] = $current->format('F Y');
                    $salesSeriesData[] = $salesMap[$key] ?? 0;
                    $purchaseSeriesData[] = $purchaseMap[$key] ?? 0;
                    $expenseSeriesDataTrend[] = $expenseMap[$key] ?? 0;
                    $current->addMonth();
                }
            }

            // Populate Main View (Daily key is used by default in view, even if granular)
            $salesChartStats['daily'] = ['categories' => $labels, 'series' => [['name' => 'Sales', 'data' => $salesSeriesData]]];
            $purchaseChartStats['daily'] = ['categories' => $labels, 'series' => [['name' => 'Purchases', 'data' => $purchaseSeriesData]]];
            $expenseTrendChartStats['daily'] = ['categories' => $labels, 'series' => [['name' => 'Expenses', 'data' => $expenseSeriesDataTrend]]];
            
            // Replicate for others to avoid bugs if user switches dropdown
            $salesChartStats['weekly'] = $salesChartStats['daily'];
            $salesChartStats['monthly'] = $salesChartStats['daily'];
            $purchaseChartStats['weekly'] = $purchaseChartStats['daily'];
            $purchaseChartStats['monthly'] = $purchaseChartStats['daily'];
            $expenseTrendChartStats['weekly'] = $expenseTrendChartStats['daily'];
            $expenseTrendChartStats['monthly'] = $expenseTrendChartStats['daily'];
        }

        // --- Other Charts ---
        $categoryProductData = DB::table('categories')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->select(
                'categories.id',
                'categories.name as category_name',
                DB::raw('COUNT(products.id) as total_products')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_products')
            ->get();

        $categoryProductChart = [
            'categories'   => $categoryProductData->pluck('category_name'),
            'category_ids' => $categoryProductData->pluck('id'),
            'series' => [
                [
                    'name' => 'Total Products',
                    'data' => $categoryProductData->pluck('total_products')
                ]
            ]
        ];

        $lowStockData = DB::table('products')
            ->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')
            ->select(
                'products.id',
                'products.item_code',
                'products.item_name',
                DB::raw('COALESCE(stocks.qty, 0) as qty'),
                'products.alert_quantity'
            )
            ->whereRaw('COALESCE(stocks.qty, 0) <= products.alert_quantity')
            ->get();
        $lowStockChart = [
            'categories' => $lowStockData->pluck('item_name'),
            'series' => [
                ['name' => 'Current Stock', 'data' => $lowStockData->pluck('qty')],
                ['name' => 'Alert Level', 'data' => $lowStockData->pluck('alert_quantity')],
            ]
        ];

        $categorySubData = DB::table('categories')
            ->leftJoin('subcategories', 'categories.id', '=', 'subcategories.category_id')
            ->leftJoin('products', 'subcategories.id', '=', 'products.sub_category_id')
            ->select(
                'categories.name as category_name',
                DB::raw('COUNT(DISTINCT subcategories.id) as sub_count'),
                DB::raw('COUNT(DISTINCT products.id) as product_count')
            )
            ->groupBy('categories.name')
            ->get();

        $categorySubChart = [
            'categories' => $categorySubData->pluck('category_name'),
            'series' => [
                ['name' => 'Subcategories', 'data' => $categorySubData->pluck('sub_count')],
                ['name' => 'Products', 'data' => $categorySubData->pluck('product_count')],
            ]
        ];

        $expenseChartData = [];
        if ($startDate && $endDate) {
             $startObj = Carbon::parse($startDate)->startOfMonth();
             $endObj   = Carbon::parse($endDate)->endOfMonth();
             
             $expenseRaw = DB::table('expense_vouchers')
                ->join('accounts', 'expense_vouchers.party_id', '=', 'accounts.id')
                ->join('account_heads', 'accounts.head_id', '=', 'account_heads.id')
                ->whereBetween('expense_vouchers.date', [$startObj->format('Y-m-d'), $endObj->format('Y-m-d')])
                ->select(
                    'account_heads.id as head_id',
                    'account_heads.name as head_name',
                    'accounts.title as account_name',
                    DB::raw('SUM(expense_vouchers.total_amount) as total_expense')
                )
                ->groupBy(
                    'account_heads.id',
                    'account_heads.name',
                    'accounts.title'
                )
                ->get()
                ->groupBy('head_id');

            foreach ($expenseRaw as $headId => $rows) {
                $expenseChartData[$headId] = [
                    'head_name' => $rows->first()->head_name,
                    'categories' => $rows->pluck('account_name'),
                    'series' => [
                        [
                            'name' => 'Expense',
                            'data' => $rows->pluck('total_expense')
                        ]
                    ]
                ];
            }
        }

        return view('admin_panel.system_reports', compact(
            'categoryCount',
            'subcategoryCount',
            'productCount',
            'customerscount',
            'totalPurchases',
            'totalPurchaseReturns',
            'totalSales',
            'totalSalesReturns',
            'totalExpenses',
            'totalCashSales',
            'totalCardSales',
            'salesChartStats',
            'purchaseChartStats',
            'expenseTrendChartStats',
            'categoryProductChart',
            'lowStockChart',
            'categorySubChart',
            'expenseChartData'
        ));
    }


    public function categoryProducts(Request $request, $id)
    {
        $search = $request->search;

        $products = DB::table('products')
            ->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')
            ->select(
                'products.id',
                'products.item_name',
                DB::raw('COALESCE(SUM(stocks.qty),0) as stock')
            )
            ->where('products.category_id', $id)
            ->when($search, function ($q) use ($search) {
                $q->where('products.item_name', 'like', "%{$search}%");
            })
            ->groupBy('products.id', 'products.item_name')
            ->orderByDesc('stock')
            ->paginate(100);

        return response()->json($products);
    }
}
