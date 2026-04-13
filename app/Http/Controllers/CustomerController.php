<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Models\CustomerCharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->get();

        $closingBalances = DB::table('customer_ledgers')
            ->select('customer_id', 'closing_balance')
            ->orderBy('id', 'desc')
            ->get()
            ->keyBy('customer_id');

        foreach ($customers as $customer) {
            $customer->closing_balance = $closingBalances[$customer->id]->closing_balance ?? 0;
        }

        $totalClosingBalance = $customers->sum('closing_balance');

        return view('admin_panel.customers.index', compact('customers', 'totalClosingBalance'));
    }

    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = $customer->status === 'active' ? 'inactive' : 'active';
        $customer->save();

        return redirect()->back()->with('success', 'Customer status updated.');
    }

    // Add this in CustomerController
    public function getCustomerLedger($id)
    {
        $ledger = CustomerLedger::where('customer_id', $id)->latest()->first();
        return response()->json([
            'closing_balance' => $ledger->closing_balance
        ]);
    }


    public function markInactive($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = 'inactive';
        $customer->save();

        return redirect()->route('customers.index')->with('success', 'Customer marked as inactive.');
    }

    public function inactiveCustomers()
    {
        $customers = Customer::where('status', 'inactive')->latest()->get();
        return view('admin_panel.customers.inactive', compact('customers'));
    }

    public function create()
    {
        $latestId = 'CUST-' . str_pad(Customer::max('id') + 1, 4, '0', STR_PAD_LEFT);
        return view('admin_panel.customers.create', compact('latestId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'      => 'required|unique:customers',
            'customer_name'    => 'required|string|max:255',
            'customer_type'    => 'nullable|string',
            'customer_category' => 'nullable|string',
            'mobile'           => 'nullable|string|max:20',
            'address'          => 'nullable|string',
            'opening_balance'  => 'nullable|numeric|min:0',
        ]);

        // Customer create
        $customer = Customer::create($data);

        // Ledger me entry agar opening balance dia gaya ho
        $opening = $data['opening_balance'] ?? 0;

        if ($opening > 0) {
            CustomerLedger::create([
                'customer_id'      => $customer->id,
                'admin_or_user_id' => Auth::id(),
                'previous_balance' => 0,
                'opening_balance'  => $opening,
                'closing_balance'  => $opening,
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }


    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('admin_panel.customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // Validation
        $data = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_type'    => 'nullable|string',
            'customer_category' => 'nullable|string',
            'mobile'           => 'nullable|string|max:20',
            'address'          => 'nullable|string',
            'opening_balance'  => 'nullable|numeric|min:0',
        ]);

        // Update customer basic info
        $customer->update($data);

        // Update ledger if opening balance is changed
        if (isset($data['opening_balance'])) {

            $ledger = CustomerLedger::where('customer_id', $customer->id)
                ->orderBy('created_at', 'asc') // first ledger entry
                ->first();

            if ($ledger) {
                $old_opening = $ledger->opening_balance;
                $difference = $data['opening_balance'] - $old_opening;

                // Update first ledger opening and closing balance
                $ledger->opening_balance = $data['opening_balance'];
                $ledger->closing_balance += $difference; // preserve all previous transactions
                $ledger->save();

                // Update all subsequent ledgers
                $subsequentLedgers = CustomerLedger::where('customer_id', $customer->id)
                    ->where('id', '>', $ledger->id)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($subsequentLedgers as $sub) {
                    $sub->previous_balance += $difference;
                    $sub->closing_balance += $difference;
                    $sub->save();
                }
            } else if ($data['opening_balance'] > 0) {
                // No ledger exists, create first entry
                CustomerLedger::create([
                    'customer_id'      => $customer->id,
                    'admin_or_user_id' => Auth::id(),
                    'previous_balance' => 0,
                    'opening_balance'  => $data['opening_balance'],
                    'closing_balance'  => $data['opening_balance'],
                ]);
            }
        }

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }


    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }


    // customer ledger start

    // Customer Ledger View
    public function customer_ledger()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $CustomerLedgers = CustomerLedger::with('customer')
                ->where('admin_or_user_id', $userId)
                ->get();
            return view('admin_panel.customers.customer_ledger', compact('CustomerLedgers'));
        } else {
            return redirect()->back();
        }
    }
    // customer payment start


    // View all customer payments
    public function customer_payments()
    {
        $payments = CustomerPayment::with('customer')->orderByDesc('id')->get();
        $customers = Customer::all();
        return view('admin_panel.customers.customer_payments', compact('payments', 'customers'));
    }

    public function store_customer_payment(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'adjustment_type' => 'required|in:plus,minus',
            'payment_method' => 'nullable|string',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $userId = Auth::id();

        // 🔹 Last received number
        $lastPayment = CustomerPayment::latest('id')->first();

        if ($lastPayment && $lastPayment->received_no) {
            $lastNumber = (int) str_replace('REC-', '', $lastPayment->received_no);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $receivedNo = 'REC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // 🔹 Save payment
        CustomerPayment::create([
            'received_no'     => $receivedNo, // ✅ AUTO
            'customer_id'     => $request->customer_id,
            'admin_or_user_id' => $userId,
            'amount'          => $request->amount,
            'payment_method'  => $request->payment_method,
            'payment_date'    => $request->payment_date,
            'note'            => $request->note,
        ]);

        // 🔹 Ledger update
        $ledger = CustomerLedger::where('customer_id', $request->customer_id)->latest()->first();

        if ($ledger) {
            $newBalance = $request->adjustment_type === 'plus'
                ? $ledger->closing_balance + $request->amount
                : $ledger->closing_balance - $request->amount;

            $ledger->update([
                'previous_balance' => $ledger->closing_balance,
                'closing_balance'  => $newBalance,
            ]);
        }

        return back()->with('success', 'Payment adjusted and ledger updated.');
    }

    public function customer_payment_receipt($id)
    {
        $payment = CustomerPayment::with('customer')->findOrFail($id);
        return view('admin_panel.customers.customer_payment_receipt', compact('payment'));
    }


    // Edit customer payment
    public function edit_customer_payment($id)
    {
        $payment = CustomerPayment::with('customer')->findOrFail($id);
        $customers = Customer::all();

        // Get current ledger balance
        $ledger = CustomerLedger::where('customer_id', $payment->customer_id)->latest()->first();
        $current_balance = $ledger ? $ledger->closing_balance : 0;

        // Calculate original balance (before this payment was made)
        // Customer payments are usually minus (received), so we ADD back to get original
        $original_balance = $current_balance + $payment->amount;

        // Determine adjustment type - default to minus for customer payments (received)
        $adjustment_type = 'minus';

        return view('admin_panel.customers.edit_customer_payment', compact('payment', 'customers', 'original_balance', 'adjustment_type'));
    }

    // Update customer payment
    public function update_customer_payment(Request $request, $id)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'note' => 'nullable|string',
            'adjustment_type' => 'required|in:plus,minus',
        ]);

        $payment = CustomerPayment::findOrFail($id);

        // Get current ledger
        $ledger = CustomerLedger::where('customer_id', $payment->customer_id)->latest()->first();

        if ($ledger) {
            // ✅ IMPORTANT: Calculate from ORIGINAL balance
            // Step 1: Get current closing balance
            $current_balance = $ledger->closing_balance;

            // Step 2: Reverse the OLD payment effect to get original balance
            // Old payment was received (minus), so we ADD it back
            $original_balance = $current_balance + $payment->amount;

            // Step 3: Apply NEW payment to the original balance
            $new_balance = $original_balance +
                ($validated['adjustment_type'] === 'minus' ? -1 : 1) * $validated['amount'];

            // Step 4: Update ledger with new balance
            $ledger->closing_balance = $new_balance;
            $ledger->save();
        }

        // Update payment record (received_no stays the same)
        $payment->update([
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'note' => $validated['note'],
            // received_no is NOT updated - it stays the same
        ]);

        return redirect()->route('customer.payments')->with('success', 'Customer payment updated successfully.');
    }

    public function destroy_payment($id)
    {
        $payment = CustomerPayment::findOrFail($id);

        $customerId = $payment->customer_id;
        $amount     = $payment->amount;

        // Latest ledger record for that customer
        $ledger = CustomerLedger::where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->first();
        if ($ledger) {
            $ledger->closing_balance += $amount;
            $ledger->save();
        }

        // Delete the payment entry
        $payment->delete();

        return redirect()->back()->with('success', 'Payment deleted and customer ledger updated successfully.');
    }


    public function getByType(Request $request)
    {
        $type = $request->get('type');

        $customers = Customer::where('customer_type', $type)->get(['id', 'customer_name']);

        return response()->json(['customers' => $customers]);
    }

    // Customer Charges (Extra expenses added to customer ledger)
    public function customer_charges()
    {
        $charges = CustomerCharge::with('customer')->orderByDesc('id')->get();
        $customers = Customer::all();
        return view('admin_panel.customers.customer_charges', compact('charges', 'customers'));
    }

    public function store_customer_charge(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'charge_no' => 'nullable|string',
            'date' => 'required|date',
            'vehicle_no' => 'nullable|string',
            'transporter_name' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $charge = CustomerCharge::create($request->all());

        // Update ledger (Plus adjustment for customer charges/expenses)
        $ledger = CustomerLedger::where('customer_id', $request->customer_id)->latest()->first();
        if ($ledger) {
            $ledger->update([
                'previous_balance' => $ledger->closing_balance,
                'closing_balance'  => $ledger->closing_balance + $request->amount,
            ]);
        }

        return back()->with('success', 'Customer charge added and ledger updated successfully.');
    }

    public function destroy_customer_charge($id)
    {
        $charge = CustomerCharge::findOrFail($id);
        $customerId = $charge->customer_id;
        $amount     = $charge->amount;

        // Reverse ledger update (Subtract from closing balance)
        $ledger = CustomerLedger::where('customer_id', $customerId)->latest()->first();
        if ($ledger) {
            $ledger->update([
                'previous_balance' => $ledger->closing_balance,
                'closing_balance'  => $ledger->closing_balance - $amount,
            ]);
        }

        $charge->delete();

        return back()->with('success', 'Customer charge deleted and ledger updated successfully.');
    }
}
