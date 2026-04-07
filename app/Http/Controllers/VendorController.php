<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorLedger;
use App\Models\VendorPayment;
use App\Models\VendorBilty;
use App\Models\Purchase;

class VendorController extends Controller
{
    // Show all vendors
    public function index()
    {
        $vendors = Vendor::with('ledger')->orderBy('id', 'desc')->get();
        $totalClosingBalance = $vendors->sum(function($vendor) {
            return (float)($vendor->ledger->closing_balance ?? 0);
        });
        return view('admin_panel.vendors.index', compact('vendors', 'totalClosingBalance'));
    }

    // Store or update vendor information
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'opening_balance' => 'nullable|numeric',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        if ($request->id) {
            // Update existing vendor
            $vendor = Vendor::findOrFail($request->id);

            // Update basic info
            $vendor->update([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? '',
                'address' => $data['address'] ?? '',
                'opening_balance' => $data['opening_balance'] ?? $vendor->opening_balance,
            ]);

            // Update ledger if opening_balance provided
            if (isset($data['opening_balance'])) {
                $ledger = VendorLedger::where('vendor_id', $vendor->id)
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($ledger) {
                    $old_opening = $ledger->opening_balance;
                    $difference = $data['opening_balance'] - $old_opening;

                    $ledger->opening_balance = $data['opening_balance'];
                    $ledger->closing_balance += $difference;
                    $ledger->save();

                    // Update subsequent ledgers
                    VendorLedger::where('vendor_id', $vendor->id)
                        ->where('id', '>', $ledger->id)
                        ->increment('previous_balance', $difference);

                    VendorLedger::where('vendor_id', $vendor->id)
                        ->where('id', '>', $ledger->id)
                        ->increment('closing_balance', $difference);
                } else {
                    // Agar ledger nahi hai to create karo
                    VendorLedger::create([
                        'vendor_id' => $vendor->id,
                        'admin_or_user_id' => Auth::id(),
                        'opening_balance' => $data['opening_balance'],
                        'closing_balance' => $data['opening_balance'],
                        'previous_balance' => 0,
                    ]);
                }
            }
        } else {
            // Create new vendor
            $vendor = Vendor::create($data);

            VendorLedger::create([
                'vendor_id' => $vendor->id,
                'admin_or_user_id' => Auth::id(),
                'opening_balance' => $data['opening_balance'] ?? 0,
                'closing_balance' => $data['opening_balance'] ?? 0,
                'previous_balance' => 0,
            ]);
        }

        return back()->with('success', 'Saved Successfully');
    }



    // Soft delete vendor and related ledger entry
    public function delete($id)
    {
        // Find the vendor by id, along with the related ledger entry using the 'ledger' relationship
        $vendor = Vendor::with('ledger')->findOrFail($id);

        // The vendor's ledger will be automatically deleted due to cascading delete
        $vendor->delete(); // Soft delete vendor

        return back()->with('success', 'Deleted Successfully');
    }


    // Show vendor ledger for the authenticated user
    public function vendors_ledger()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $VendorLedgers = VendorLedger::with('vendor')->get();

            return view('admin_panel.vendors.vendors_ledger', compact('VendorLedgers'));
        } else {
            return redirect()->back();
        }
    }

    public function vendor_payments()
    {
        $query = VendorPayment::with('vendor');

        // 🔹 Restrict non-admin users to their own payments
        // Users with Role 'Admin' or ID 1 (Super Admin) can see/manage everything
        if (Auth::id() !== 1 && !Auth::user()->hasRole('Admin')) {
            $query->where('admin_or_user_id', Auth::id());
        }

        $payments = $query->orderByDesc('id')->get();
        $vendors = Vendor::all();
        return view('admin_panel.vendors.vendor_payments', compact('payments', 'vendors'));
    }

    // Store vendor payment and update ledger
    public function store_vendor_payment(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'note' => 'nullable|string',
            'adjustment_type' => 'required|in:plus,minus',
        ]);

        // 🔹 Last payment number
        $lastPayment = VendorPayment::latest('id')->first();

        if ($lastPayment && $lastPayment->payment_no) {
            $lastNumber = (int) str_replace('PAY-', '', $lastPayment->payment_no);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        $paymentNo = 'PAY-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        // 🔹 Save payment
        $payment = VendorPayment::create([
            'payment_no' => $paymentNo, // ✅ AUTO
            'vendor_id' => $request->vendor_id,
            'admin_or_user_id' => Auth::id(),
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'note' => $request->note,
        ]);

        // 🔹 Update vendor ledger
        $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->first();
        if ($ledger) {
            $ledger->closing_balance +=
                ($request->adjustment_type === 'minus' ? -1 : 1) * $request->amount;
            $ledger->save();
        }

        return redirect()->back()->with('success', 'Vendor payment recorded.');
    }

    // Edit vendor payment
    public function edit_vendor_payment($id)
    {
        $payment = VendorPayment::with('vendor')->findOrFail($id);
        $vendors = Vendor::all();
        
        // Get current ledger balance
        $ledger = VendorLedger::where('vendor_id', $payment->vendor_id)->first();
        $current_balance = $ledger ? $ledger->closing_balance : 0;
        
        // Calculate original balance (before this payment was made)
        // If minus: original = current + amount (we deducted, so add back)
        // If plus: original = current - amount (we added, so subtract)
        $original_balance = $current_balance + $payment->amount; // Assuming payment was minus
        
        // Determine adjustment type based on stored data or default to minus
        $adjustment_type = 'minus'; // Default assumption for payments
        
        return view('admin_panel.vendors.edit_vendor_payment', compact('payment', 'vendors', 'original_balance', 'adjustment_type'));
    }

    // Update vendor payment
    public function update_vendor_payment(Request $request, $id)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'note' => 'nullable|string',
            'adjustment_type' => 'required|in:plus,minus',
        ]);

        $payment = VendorPayment::findOrFail($id);
        
        // 1. Revert Old Payment Logic (Add back amount to Old Vendor)
        // Currently assuming all existing payments were 'minus' (standard payments)
        // If we store type later, we should check $payment->type here.
        $oldLedger = VendorLedger::where('vendor_id', $payment->vendor_id)->first();
        if ($oldLedger) {
            $oldLedger->closing_balance += $payment->amount; // Add back the deducted amount
            $oldLedger->save();
        }

        // 2. Apply New Payment Logic to New Vendor
        // Fetch fresh ledger for the new vendor (could be same vendor, but need updated state)
        $newLedger = VendorLedger::where('vendor_id', $validated['vendor_id'])->first();
        
        // If no ledger exists for new vendor, create one? 
        // Ideally should exist if vendor created properly. 
        // But for safety, check if exists.
        if ($newLedger) {
            $adjustment = ($validated['adjustment_type'] === 'minus' ? -1 : 1) * $validated['amount'];
            $newLedger->closing_balance += $adjustment;
            $newLedger->save();
        } else {
             // Create ledger if missing (Edge case)
             VendorLedger::create([
                'vendor_id' => $validated['vendor_id'],
                'admin_or_user_id' => Auth::id(),
                'opening_balance' => 0,
                // Initial balance is just this transaction
                'closing_balance' => ($validated['adjustment_type'] === 'minus' ? -1 : 1) * $validated['amount'], 
                'previous_balance' => 0,
            ]);
        }
        
        // 3. Update Payment Record
        $payment->update([
            'vendor_id' => $validated['vendor_id'], // ✅ Update vendor
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'note' => $validated['note'],
            // payment_no stays same
        ]);

        return redirect()->route('vendor.payments')->with('success', 'Vendor payment updated successfully.');
    }


    public function printReceipt($id)
    {
        $payment = VendorPayment::with('vendor')->findOrFail($id);
        return view('admin_panel.vendors.payment_receipt', compact('payment'));
    }
    // Show all vendor bilties
    public function vendor_bilties()
    {
        $bilties = VendorBilty::with(['vendor', 'purchase'])->orderByDesc('id')->get();
        $vendors = Vendor::all();
        $purchases = Purchase::all();
        return view('admin_panel.vendors.vendor_bilties', compact('bilties', 'vendors', 'purchases'));
    }

    // Store vendor bilty information
    public function store_vendor_bilty(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'bilty_no' => 'nullable|string',
            'vehicle_no' => 'nullable|string',
            'transporter_name' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        VendorBilty::create($request->all());

        return back()->with('success', 'Vendor bilty saved successfully.');
    }

    // Get vendor balance by vendor id
    public function getVendorBalance($id)
    {
        $ledger = VendorLedger::where('vendor_id', $id)->first();
        return response()->json([
            'closing_balance' => $ledger ? $ledger->closing_balance : 0
        ]);
    }

    public function destroy_payment($id)
    {
        $payment = VendorPayment::findOrFail($id);
        
        // Revert ledger balance
        $ledger = VendorLedger::where('vendor_id', $payment->vendor_id)->first();
        if ($ledger) {
            // If it was a payment (minus), we add it back. 
            // If we support return (plus), we subtract.
            // Currently assuming 'minus' as per store logic default or check if you have 'type' column.
            // Based on store_vendor_payment, it seems we might not be storing 'type' in payment table explicitly yet?
            // Checking store method: 'adjustment_type' is used but not stored in VendorPayment model in the create call?
            // Wait, looking at store_vendor_payment:
            // VendorPayment::create([... 'amount' => $request->amount ...]);
            // It doesn't seem to store 'type'.
            // However, typically payments are 'minus' (money going out to vendor).
            // If 'plus' (advance return), it would be money coming back?
            // Use standard logic: Payment = Debit from our cash/bank, Credit to Vendor (Liability decreases).
            // So Vendor Ledger Closing Balance (Liability) Decreases.
            // To Revert: Increase Vendor Ledger Closing Balance.
            
            $ledger->closing_balance = (float)$ledger->closing_balance + (float)$payment->amount; 
            $ledger->save();
        }

        $payment->delete();

        return back()->with('success', 'Payment deleted and ledger updated successfully.');
    }
}
