<?php


namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Narration;
use App\Models\Vendor;
use Illuminate\Http\Request;

class NarrationController extends Controller
{
    public function index()
    {
        $narrations = Narration::latest()->get();
        return view('admin_panel.accounts.narration', compact('narrations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_head' => 'required|string|max:255',
            'narration' => 'required|string',
        ]);

        if ($request->id) {
            // Update
            $narration = Narration::findOrFail($request->id);
            $narration->update([
                'expense_head' => $request->expense_head,
                'narration' => $request->narration,
            ]);
            return redirect()->back()->with('success', 'Narration updated successfully.');
        } else {
            // Create
            Narration::create([
                'expense_head' => $request->expense_head,
                'narration' => $request->narration,
            ]);
            return redirect()->back()->with('success', 'Narration added successfully.');
        }
    }

    public function destroy($id)
    {
        Narration::findOrFail($id)->delete();
        return redirect()->route('narrations.index')->with('success', 'Narration deleted successfully.');
    }

    public function getPartyList(Request $request)
    {
        $type = strtolower($request->query('type', 'vendor'));

        if ($type === 'vendor') {
            $vendors = Vendor::select('id', 'name as text')->get();
            return response()->json($vendors);
        } elseif ($type === 'customer') {
            $customers = Customer::where('customer_type', 'Main Customer')
                ->select('id', 'customer_name as text')
                ->get();
            return response()->json($customers);
        } elseif ($type === 'walkin') {
            $walkins = Customer::where('customer_type', 'Walking Customer')
                ->select('id', 'customer_name as text')
                ->get();
            return response()->json($walkins);
        }

        return response()->json([]);
    }

    public function getCustomerData($id, Request $request)
    {
        $type = strtolower($request->query('type', 'customer'));

        if ($type === 'vendor') {
            // Fetch Vendor data
            $v = Vendor::find($id);
            if (!$v) {
                return response()->json(['error' => 'Vendor not found'], 404);
            }

            return response()->json([
                'address' => $v->address,
                'mobile' => $v->phone, // assuming 'phone' field for vendors
                'remarks' => '', // No remarks for vendors
                'previous_balance' => 0, // Vendors may not have balance logic
            ]);
        }

        // Default: Fetch Customer data (including walking)
        $c = Customer::find($id);
        if (!$c) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Retrieve the latest ledger entry for the customer
        $latestLedger = CustomerLedger::where('customer_id', $id)->latest()->first();

        // If a ledger entry exists, use its closing_balance; otherwise, set it to 0
        $previous_balance = $latestLedger ? $latestLedger->closing_balance : 0;

        return response()->json([
            'filer_type' => $c->filer_type,
            'customer_type' => $c->customer_type,
            'address' => $c->address,
            'mobile' => $c->mobile,
            'remarks' => $c->remarks ?? '',
            'previous_balance' => $previous_balance, // Use the latest closing_balance
        ]);
    }

    public function getAccountsByHead($headId)
    {
        $accounts = Account::where('head_id', $headId)->where('status', 1)->get();
        return response()->json($accounts);
    }
}
