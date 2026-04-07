<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\ExpenseVoucher;
use Illuminate\Http\Request;

class AccountsHeadController extends Controller
{
    public function index()
    {
        $accounts = Account::with('head')->get();
        // dd( $accounts->toArray());
        $heads = AccountHead::all();
        return view('admin_panel.chart_of_accounts', compact('accounts', 'heads'));
    }
    public function storeHead(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        AccountHead::create(['name' => $request->name]);
        return redirect()->back()->with('success', 'Head added successfully.');
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'head_id' => 'required|exists:account_heads,id',
            'account_code' => 'required',
            'title' => 'required|string|max:150',
            'opening_balance' => 'nullable|numeric',
        ]);

        $status = $request->status === 'on' ? 1 : 0;

        // 🔥 UPDATE
        if ($request->account_id) {

            $account = Account::findOrFail($request->account_id);

            $account->update([
                'head_id'         => $request->head_id,
                'account_code'    => $request->account_code,
                'title'           => $request->title,
                'opening_balance' => $request->opening_balance ?? 0,
                'status'          => $status,
            ]);

            return redirect()->back()->with('success', 'Account updated successfully.');
        }

        // 🔥 CREATE
        $request->validate([
            'account_code' => 'unique:accounts,account_code',
        ]);

        Account::create([
            'head_id'         => $request->head_id,
            'account_code'    => $request->account_code,
            'title'           => $request->title,
            'opening_balance' => $request->opening_balance ?? 0,
            'status'          => $status,
        ]);

        return redirect()->back()->with('success', 'Account added successfully.');
    }

    public function destroy($id)
    {
        $account = Account::findOrFail($id);

        // 🔒 Check: account used in expense vouchers
        $usedInExpense = ExpenseVoucher::where('party_id', $account->id)
            ->orWhereJsonContains('row_account_id', (string) $account->id)
            ->exists();

        if ($usedInExpense) {
            return redirect()->back()->with(
                'error',
                'You cannot delete this account because expenses have already been recorded against it.'
            );
        }

        $account->delete();

        return redirect()->back()->with('success', 'Account deleted successfully.');
    }
}
