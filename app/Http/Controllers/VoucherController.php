<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\CustomerLedger;
use App\Models\ExpenseVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use App\Models\Narration;
use App\Models\PaymentVoucher;
use App\Models\ReceiptsVoucher;
use App\Models\VendorLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{

    public function all_recepit_vochers()
    {
        $receipts = \App\Models\ReceiptsVoucher::orderBy('id', 'DESC')->get();

        foreach ($receipts as $voucher) {
            $partyName = '-';
            $typeLabel = '-';

            // 🧩 Check if type is numeric → account-based
            if (is_numeric($voucher->type)) {
                $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
                $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

                $typeLabel = $accountHead->name ?? 'Account';
                $partyName = $account->title ?? '-';
            } elseif ($voucher->type === 'vendor') {
                $vendor = DB::table('vendors')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Vendor';
                $partyName = $vendor->name ?? '-';
            } elseif ($voucher->type === 'customer') {
                $customer = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Customer';
                $partyName = $customer->customer_name ?? '-';
            } elseif ($voucher->type === 'walkin') {
                $walkin = DB::table('customers')
                    ->where('id', $voucher->party_id)
                    ->where('customer_type', 'Walking Customer')
                    ->first();
                $typeLabel = 'Walk-in';
                $partyName = $walkin->customer_name ?? '-';
            }

            // Attach new properties to the object
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;
        }

        return view('admin_panel.vochers.all_recepit_vochers', compact('receipts'));
    }

    public function recepit_vochers()
    {
        $narrations = \App\Models\Narration::where('expense_head', 'Receipts Voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::get();

        // Last RVID nikalna
        $lastVoucher = \App\Models\ReceiptsVoucher::latest('id')->first();

        // Next ID generate karna
        $nextId = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $nextRvid = 'RVID-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.reciepts_vouchers', compact('narrations', 'AccountHeads', 'nextRvid'));
    }

    public function store_rec_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $rvid = ReceiptsVoucher::generateInvoiceNo();

            $narrationIds = [];

            foreach ($request->narration_id as $index => $narrId) {
                $manualText = $request->narration_text[$index] ?? null;
                $manualType = $request->narration_type_text[$index] ?? 'Manual';

                if (empty($narrId) && !empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Receipts Voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Receipts Voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration'    => $manualText,
                    ]);

                    $narrationIds[] = (string)$new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string)$narrId; // force string format
                }
            }

            $voucherData = [
                'rvid'             => $rvid,
                'receipt_date'     => $request->receipt_date,
                'entry_date'       => $request->entry_date,
                'type'             => $request->vendor_type,
                'party_id'         => $request->vendor_id,
                'tel'              => $request->tel,
                'remarks'          => $request->remarks,

                'narration_id' => json_encode($narrationIds),
                'reference_no'     => json_encode($request->reference_no),
                'row_account_head' => json_encode($request->row_account_head),
                'row_account_id'   => json_encode($request->row_account_id),
                'discount_value'   => json_encode($request->discount_value),
                'kg'               => json_encode($request->kg),
                'rate'             => json_encode($request->rate),
                'amount'           => json_encode($request->amount),
                'total_amount'     => $request->total_amount,
            ];

            ReceiptsVoucher::create($voucherData);

            // ✅ Ledger update logic
            $amount = (float)$request->total_amount;

            if ($request->vendor_type === 'vendor') {
                $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->latest()->first();

                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance - $amount;
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id'        => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'date'             => now(),
                        'description'      => "Receipt Voucher #$rvid",
                        'opening_balance'  => 0,
                        'debit'            => 0,
                        'credit'           => $amount,
                        'previous_balance' => 0,
                        'closing_balance'  => -$amount,
                    ]);
                }
            } elseif ($request->vendor_type === 'customer') {
                $ledger = CustomerLedger::where('customer_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance - $amount;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance'  => 0,
                        'closing_balance'  => -$amount,
                    ]);
                }
            } else {
                // Bank/Head case → pehle vendor/account side minus
                $account = Account::find($request->vendor_id);
                if ($account) {
                    $account->opening_balance = $account->opening_balance - $amount;
                    $account->save();
                }
            }

            // ✅ Har case me row_account_id ka + hona zaroori hai
            if ($request->row_account_id && $request->amount) {
                foreach ($request->row_account_id as $index => $accId) {
                    $rowAmount = isset($request->amount[$index]) ? (float)$request->amount[$index] : 0;

                    if ($rowAmount > 0) {
                        $rowAccount = Account::find($accId);
                        if ($rowAccount) {
                            $rowAccount->opening_balance = $rowAccount->opening_balance + $rowAmount;
                            $rowAccount->save();
                        }
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Receipt Voucher saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function print($id)
    {
        // Find voucher or fail
        $voucher = ReceiptsVoucher::findOrFail($id);

        // --- Setup display datetime (single source for date & time) ---
        $tz = config('app.timezone') ?: 'Asia/Karachi';

        $receipt = $voucher->receipt_date;
        $created = $voucher->created_at;
        $updated = $voucher->updated_at;
        $entry = $voucher->entry_date; // might be date-only (Y-m-d)

        if (!empty($receipt)) {
            try {
                $date = Carbon::parse($receipt)->toDateString(); // only date
                $time = !empty($created)
                    ? Carbon::parse($created)->toTimeString()
                    : '00:00:00';

                $dt = Carbon::parse("$date $time");
            } catch (\Exception $e) {
                $dt = !empty($created) ? Carbon::parse($created) : Carbon::now();
            }
        } elseif (!empty($created)) {
            $dt = Carbon::parse($created);
        } elseif (!empty($updated)) {
            $dt = Carbon::parse($updated);
        } elseif (!empty($entry)) {
            // entry_date may be date-only. Try to combine with created_at time if available.
            try {
                if (!empty($created)) {
                    $datePart = Carbon::parse($entry)->toDateString();
                    $timePart = Carbon::parse($created)->toTimeString();
                    $dt = Carbon::parse($datePart . ' ' . $timePart);
                } else {
                    // will be start of day 00:00:00
                    $dt = Carbon::parse($entry);
                }
            } catch (\Exception $e) {
                $dt = Carbon::now();
            }
        } else {
            $dt = Carbon::now();
        }

        // Convert to app timezone for display
        try {
            $dt = $dt->setTimezone($tz);
        } catch (\Exception $e) {
            // fallback
            $dt = $dt->setTimezone('Asia/Karachi');
        }

        // Attach for view use
        $voucher->display_datetime = $dt;

        // --- Decode JSON arrays safely ---
        $narrations   = is_string($voucher->narration_id) ? json_decode($voucher->narration_id, true) : ($voucher->narration_id ?? []);
        $references   = is_string($voucher->reference_no) ? json_decode($voucher->reference_no, true) : ($voucher->reference_no ?? []);
        $accountHeads = is_string($voucher->row_account_head) ? json_decode($voucher->row_account_head, true) : ($voucher->row_account_head ?? []);
        $accounts     = is_string($voucher->row_account_id) ? json_decode($voucher->row_account_id, true) : ($voucher->row_account_id ?? []);
        $amounts      = is_string($voucher->amount) ? json_decode($voucher->amount, true) : ($voucher->amount ?? []);

        // Ensure arrays
        $narrations   = is_array($narrations) ? $narrations : [];
        $references   = is_array($references) ? $references : [];
        $accountHeads = is_array($accountHeads) ? $accountHeads : [];
        $accounts     = is_array($accounts) ? $accounts : [];
        $amounts      = is_array($amounts) ? $amounts : [];

        // --- Build rows for items table ---
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = null;
            if (!empty($narrId)) {
                $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            }

            $ref = $references[$index] ?? null;

            $accountHeadName = null;
            if (!empty($accountHeads[$index])) {
                $accountHeadName = DB::table('account_heads')->where('id', $accountHeads[$index])->value('name');
            }

            $accountObj = null;
            if (!empty($accounts[$index])) {
                $accountObj = DB::table('accounts')->where('id', $accounts[$index])->first();
            }

            $amount = (float) ($amounts[$index] ?? 0);

            $rows[] = [
                'narration'     => $narration,
                'reference'     => $ref,
                'account_head'  => $accountHeadName,
                'account_name'  => $accountObj->title ?? null,
                'account_code'  => $accountObj->account_code ?? null,
                'amount'        => $amount,
            ];
        }

        // --- Party (depends on voucher->type) ---
        $party = null;
        $previousBalance = 0;

        // If type is numeric — treat as account head id (legacy pattern you used)
        if (is_numeric($voucher->type)) {
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object)[
                    'name' => $account->title ?? '—',
                    'address' => $account->address ?? '—',
                    'mobile' => $account->mobile ?? $account->phone ?? $account->account_code ?? '—',
                    'phone' => $account->phone ?? $account->mobile ?? $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }
        } elseif ($voucher->type === 'vendor') {
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;
        } elseif ($voucher->type === 'customer') {
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;
        } elseif ($voucher->type === 'walkin') {
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        } else {
            // fallback: try to lookup as customer or vendor
            $tryCustomer = DB::table('customers')->where('id', $voucher->party_id)->first();
            if ($tryCustomer) {
                $party = $tryCustomer;
                $previousBalance = DB::table('customer_ledgers')
                    ->where('customer_id', $voucher->party_id)
                    ->orderByDesc('id')
                    ->value('closing_balance') ?? 0;
            } else {
                $tryVendor = DB::table('vendors')->where('id', $voucher->party_id)->first();
                if ($tryVendor) {
                    $party = $tryVendor;
                    $previousBalance = DB::table('vendor_ledgers')
                        ->where('vendor_id', $voucher->party_id)
                        ->orderByDesc('id')
                        ->value('closing_balance') ?? 0;
                }
            }
        }

        // Make sure previousBalance is numeric
        $previousBalance = is_numeric($previousBalance) ? (float)$previousBalance : 0.0;

        // Pass everything to view
        return view('admin_panel.vochers.print', compact(
            'voucher',
            'rows',
            'party',
            'previousBalance'
        ));
    }
    // Payment vocher
    public function Payment_vochers()
    {
        $narrations = \App\Models\Narration::where('expense_head', 'Payment voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::get();

        // Last RVID nikalna
        $lastVoucher = \App\Models\PaymentVoucher::latest('id')->first();

        // Next ID generate karna
        $nextId = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $nextPVID = 'PVID-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.payment_vochers.payment_vouchers', compact('narrations', 'AccountHeads', 'nextPVID'));
    }

    public function store_Pay_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $pvid = PaymentVoucher::generateInvoiceNo();
            $narrationIds = [];

            foreach ($request->narration_id as $index => $narrId) {
                $manualText = $request->narration_text[$index] ?? null;
                $manualType = $request->narration_type_text[$index] ?? 'Manual';

                if (empty($narrId) && !empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Payment voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Payment voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration'    => $manualText,
                    ]);

                    $narrationIds[] = (string)$new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string)$narrId; // force string format
                }
            }
            $voucherData = [
                'pvid'             => $pvid,
                'receipt_date'     => $request->receipt_date,
                'entry_date'       => $request->entry_date,
                'type'             => $request->vendor_type,
                'party_id'         => $request->vendor_id,
                'tel'              => $request->tel,
                'remarks'          => $request->remarks,
                'narration_id' => json_encode($narrationIds),
                'reference_no'     => json_encode($request->reference_no),
                'row_account_head' => json_encode($request->row_account_head),
                'row_account_id'   => json_encode($request->row_account_id),
                'discount_value'   => json_encode($request->discount_value),
                'kg'               => json_encode($request->kg),
                'rate'             => json_encode($request->rate),
                'amount'           => json_encode($request->amount),
                'total_amount'     => $request->total_amount,
            ];

            PaymentVoucher::create($voucherData);

            $amount = (float)$request->total_amount;
            /**
             * STEP 1: Row accounts → MINUS (opposite of receipt voucher)
             */
            if ($request->row_account_id && $request->amount) {
                foreach ($request->row_account_id as $index => $accId) {
                    $rowAmount = isset($request->amount[$index]) ? (float)$request->amount[$index] : 0;

                    if ($rowAmount > 0) {
                        $rowAccount = Account::find($accId);
                        if ($rowAccount) {
                            $rowAccount->opening_balance = $rowAccount->opening_balance - $rowAmount;
                            $rowAccount->save();
                        }
                    }
                }
            }

            /**
             * STEP 2: Party side (Vendor / Customer / Account Head) → PLUS
             */
            if ($request->vendor_type === 'vendor') {
                $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance + $amount;
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id'        => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'date'             => now(),
                        'description'      => "Payment Voucher #$pvid",
                        'opening_balance'  => 0,
                        'debit'            => $amount,
                        'credit'           => 0,
                        'previous_balance' => 0,
                        'closing_balance'  => $amount,
                    ]);
                }
            } elseif ($request->vendor_type === 'customer') {
                $ledger = CustomerLedger::where('customer_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance + $amount;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance'  => 0,
                        'closing_balance'  => $amount,
                    ]);
                }
            } else {
                // agar vendor_type me account head/account ki id ayi
                $account = Account::find($request->vendor_id);
                if ($account) {
                    $account->opening_balance = $account->opening_balance + $amount;
                    $account->save();
                }
            }

            DB::commit();
            return back()->with('success', 'Payment Voucher saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function all_Payment_vochers()
    {
        $receipts = PaymentVoucher::orderBy('id', 'DESC')->get();
        return view('admin_panel.vochers.payment_vochers.all_payment_vochers', compact('receipts'));
    }

    public function Paymentprint($id)
    {
        $voucher = \App\Models\PaymentVoucher::findOrFail($id);

        // Decode JSON arrays
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // 🧾 Build detailed rows
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float)($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party setup — dynamic based on type
        $party = null;
        $previousBalance = 0;

        // ✅ Account Head type (numeric)
        if (is_numeric($voucher->type)) {
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object)[
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            $previousBalance = $account->opening_balance ?? 0;

            // ✅ Vendor
        } elseif ($voucher->type === 'vendor') {
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ Customer
        } elseif ($voucher->type === 'customer') {
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ Walking customer
        } elseif ($voucher->type === 'walkin') {
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.payment_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }

    public function expense_vochers()
    {
        $narrations = \App\Models\Narration::where('expense_head', 'Expense voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::get();

        // Last RVID nikalna
        $lastVoucher = \App\Models\ExpenseVoucher::latest('id')->first();

        // Next ID generate karna
        $nextId = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $nextRvid = 'EVID-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.expense_vochers.expense_vouchers', compact('narrations', 'AccountHeads', 'nextRvid'));
    }

    public function store_expense_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'evid'        => 'required',
                'date'        => 'required',
                'vendor_type' => 'required', // Account Head ID
                'vendor_id'   => 'required', // Account ID
                'remarks'     => 'required|array',
                'amount'      => 'required|array',
            ]);
            // ✅ Calculate total
            $totalAmount = 0;
            foreach ($request->amount as $amt) {
                $totalAmount += (float)$amt;
            }

            // ✅ Save Voucher
            $voucher = ExpenseVoucher::create([
                'evid'         => $request->evid,
                'entry_date'   => now(),
                'date'         => $request->date,
                'type'         => $request->vendor_type, // Account Head
                'party_id'     => $request->vendor_id,   // Account
                'remarks'      => json_encode($request->remarks),
                'amount'       => json_encode($request->amount),
                'total_amount' => $totalAmount,
            ]);

            foreach ($request->amount as $amt) {
                if ($amt > 0) {
                    $expenseAccount = Account::where('head_id', $request->vendor_type)->first();
                    if ($expenseAccount) {
                        $expenseAccount->opening_balance += (float)$amt;
                        $expenseAccount->save();
                    }
                }
            }

            $payingAccount = Account::find($request->vendor_id);
            if ($payingAccount) {
                $payingAccount->opening_balance -= $totalAmount;
                $payingAccount->save();
            }

            DB::commit();
            return back()->with('success', 'Expense Voucher saved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }


    public function all_expense_vochers()
    {
        $vouchers = ExpenseVoucher::with(['accountHeadType', 'partyAccount', 'vendor', 'customer'])->orderBy('id', 'desc')->get();
        return view('admin_panel.vochers.expense_vochers.all_expense_vochers', compact('vouchers'));
    }

    public function edit_expense_voucher($id)
    {
        $voucher = ExpenseVoucher::findOrFail($id);
        $AccountHeads = AccountHead::get();
        // Load accounts for the current head
        $accounts = \App\Models\Account::where('head_id', $voucher->type)->get();
        
        return view('admin_panel.vochers.expense_vochers.edit_expense_voucher', compact('voucher', 'AccountHeads', 'accounts'));
    }

    public function update_expense_voucher(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'date'        => 'required',
                'vendor_type' => 'required', // Account Head ID
                'vendor_id'   => 'required', // Account ID
                'remarks'     => 'required|array',
                'amount'      => 'required|array',
            ]);

            $voucher = ExpenseVoucher::findOrFail($id);

            // --- REVERSE OLD BALANCES ---
            // 1. Reverse paying account
            $oldPayingAccount = Account::find($voucher->party_id);
            if ($oldPayingAccount) {
                $oldPayingAccount->opening_balance += $voucher->total_amount;
                $oldPayingAccount->save();
            }

            // 2. Reverse expense accounts
            $oldAmounts = is_array($voucher->amount) ? $voucher->amount : (json_decode($voucher->amount, true) ?? []);
            foreach ($oldAmounts as $amt) {
                if ($amt > 0) {
                    $expenseAccount = Account::where('head_id', $voucher->type)->first();
                    if ($expenseAccount) {
                        $expenseAccount->opening_balance -= (float)$amt;
                        $expenseAccount->save();
                    }
                }
            }

            // --- APPLY NEW BALANCES ---
            $totalAmount = 0;
            foreach ($request->amount as $amt) {
                $totalAmount += (float)$amt;
            }

            $voucher->update([
                'date'         => $request->date,
                'type'         => $request->vendor_type,
                'party_id'     => $request->vendor_id,
                'remarks'      => json_encode($request->remarks),
                'amount'       => json_encode($request->amount),
                'total_amount' => $totalAmount,
            ]);

            foreach ($request->amount as $amt) {
                if ($amt > 0) {
                    $expenseAccount = Account::where('head_id', $request->vendor_type)->first();
                    if ($expenseAccount) {
                        $expenseAccount->opening_balance += (float)$amt;
                        $expenseAccount->save();
                    }
                }
            }

            $payingAccount = Account::find($request->vendor_id);
            if ($payingAccount) {
                $payingAccount->opening_balance -= $totalAmount;
                $payingAccount->save();
            }

            DB::commit();
            return redirect()->route('all-expense-vochers')->with('success', 'Expense Voucher updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function expenseprint($id)
    {
        $voucher = ExpenseVoucher::findOrFail($id);

        // ✅ Decode remarks & amounts
        $remarks = json_decode($voucher->remarks, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // ✅ Build rows (Remarks + Amount only)
        $rows = [];
        foreach ($remarks as $i => $remark) {
            $rows[] = [
                'description' => $remark,
                'amount'      => (float)($amounts[$i] ?? 0),
            ];
        }

        // ✅ Party is always ACCOUNT now
        $account = \App\Models\Account::find($voucher->party_id);

        $party = (object)[
            'name'    => $account->title ?? '-',
            'phone'   => $account->account_code ?? '-',
            'address' => '-',
        ];

        // ✅ Previous balance (simple)
        $previousBalance = $account->opening_balance ?? 0;

        return view('admin_panel.vochers.expense_vochers.print',
            compact('voucher', 'rows', 'party', 'previousBalance')
        );
    }
}
