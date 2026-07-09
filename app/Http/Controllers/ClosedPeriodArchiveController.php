<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use App\Models\ExpenseVoucher;
use App\Models\InwardGatepass;
use App\Models\InwardReturn;
use App\Models\ProductBooking;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PeriodClosing\ArchiveListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClosedPeriodArchiveController extends Controller
{
    public function __construct(
        private ArchiveListingService $archiveListing
    ) {}

    public function index()
    {
        $periods = AccountingPeriod::where('status', 'closed')
            ->with('closedBy')
            ->orderByDesc('end_date')
            ->get();

        return view('admin_panel.period_closing.archive_index', compact('periods'));
    }

    public function show(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $period->load(['snapshots', 'closedBy']);

        $counts = [
            'sales' => DB::table('sales')->where('accounting_period_id', $period->id)->count(),
            'purchases' => DB::table('purchases')->where('accounting_period_id', $period->id)->whereNull('deleted_at')->count(),
            'sales_returns' => DB::table('sales_returns')->where('accounting_period_id', $period->id)->count(),
            'purchase_returns' => DB::table('purchase_returns')->where('accounting_period_id', $period->id)->count(),
            'inward_gatepasses' => DB::table('inward_gatepasses')->where('accounting_period_id', $period->id)->count(),
            'inward_returns' => DB::table('inward_returns')->where('accounting_period_id', $period->id)->count(),
            'expense_vouchers' => DB::table('expense_vouchers')->where('accounting_period_id', $period->id)->count(),
            'product_bookings' => DB::table('product_bookings')->where('accounting_period_id', $period->id)->count(),
        ];

        return view('admin_panel.period_closing.archive_show', compact('period', 'counts'));
    }

    public function sales(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $title = 'ARCHIVE — SALES';
        return view('admin_panel.period_closing.archive_sales', compact('period', 'title'));
    }

    public function salesFetch(Request $request, AccountingPeriod $period)
    {
        $this->assertClosed($period);
        return response()->json($this->archiveListing->salesDataTable($request, $period->id));
    }

    public function salesReturns(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $title = 'ARCHIVE — SALE RETURNS';
        return view('admin_panel.period_closing.archive_sales_returns', compact('period', 'title'));
    }

    public function salesReturnsFetch(Request $request, AccountingPeriod $period)
    {
        $this->assertClosed($period);
        return response()->json($this->archiveListing->salesReturnsDataTable($request, $period->id));
    }

    public function bookings(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $title = 'ARCHIVE — BOOKINGS';
        $bookings = ProductBooking::with('customer_relation')
            ->where('accounting_period_id', $period->id)
            ->latest()
            ->get();
        return view('admin_panel.period_closing.archive_bookings', compact('period', 'title', 'bookings'));
    }

    public function purchases(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $title = 'ARCHIVE — PURCHASES';

        $purchases = Purchase::with(['branch', 'warehouse', 'vendor', 'items.product', 'return'])
            ->where('accounting_period_id', $period->id)
            ->get();

        $inwards = InwardGatepass::with(['branch', 'warehouse', 'vendor', 'items.product'])
            ->where('accounting_period_id', $period->id)
            ->where('status', 'linked')
            ->where('bill_status', 'billed')
            ->get();

        $records = $purchases->concat($inwards)->sortByDesc(function ($row) {
            $date = $row instanceof InwardGatepass ? $row->gatepass_date : $row->purchase_date;
            return \Carbon\Carbon::parse($date)->timestamp;
        })->values();

        return view('admin_panel.period_closing.archive_purchases', compact('period', 'title', 'records'));
    }

    public function purchaseReturns(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $title = 'ARCHIVE — PURCHASE RETURNS';
        $returns = PurchaseReturn::with(['vendor', 'warehouse', 'purchase', 'items.product'])
            ->where('accounting_period_id', $period->id)
            ->latest()
            ->get();
        return view('admin_panel.period_closing.archive_purchase_returns', compact('period', 'title', 'returns'));
    }

    public function inwards(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $title = 'ARCHIVE — INWARD GATEPASS';
        $gatepasses = InwardGatepass::with(['items.product', 'branch', 'warehouse', 'vendor'])
            ->where('accounting_period_id', $period->id)
            ->orderByDesc('id')
            ->get();
        return view('admin_panel.period_closing.archive_inwards', compact('period', 'title', 'gatepasses'));
    }

    public function inwardReturns(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $title = 'ARCHIVE — INWARD RETURNS';
        $inwardReturns = InwardReturn::with(['vendor', 'warehouse', 'inwardGatepass'])
            ->where('accounting_period_id', $period->id)
            ->orderByDesc('id')
            ->get();
        return view('admin_panel.period_closing.archive_inward_returns', compact('period', 'title', 'inwardReturns'));
    }

    public function expenses(AccountingPeriod $period)
    {
        $this->assertClosed($period);
        $title = 'ARCHIVE — EXPENSE VOUCHERS';
        $vouchers = ExpenseVoucher::with(['accountHeadType', 'partyAccount', 'vendor', 'customer'])
            ->where('accounting_period_id', $period->id)
            ->orderByDesc('id')
            ->get();
        $totalExpense = $vouchers->sum('total_amount');
        return view('admin_panel.period_closing.archive_expenses', compact('period', 'title', 'vouchers', 'totalExpense'));
    }

    private function assertClosed(AccountingPeriod $period): void
    {
        if ($period->status !== 'closed') {
            abort(404);
        }
    }
}
