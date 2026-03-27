<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\UserOpeningBalance;

class UserController extends Controller
{
    public function index()
    {

        $users = User::all();
        $allRoles  = Role::all();
        
        // attach latest opening balance (on or before today)
        foreach($users as $user) {
            $user->today_opening = UserOpeningBalance::where('user_id', $user->id)
                ->where('date', '<=', date('Y-m-d'))
                ->orderBy('date', 'desc')
                ->first();
        }

        return view('admin_panel.users.users', compact(['users', 'allRoles']));
    }

    public function store(Request $request)
    {
        // dd("sda");
        $editId = $request->edit_id ?? null;
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $request->edit_id,
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }



        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        // Step 2: Check for user_id uniqueness (exclude self in edit)
        // $userExists = Branch::where('user_id', $request->user_id)
        //     ->when($editId, fn($q) => $q->where('id', '!=', $editId))
        //     ->exists();

        // if ($userExists) {
        //     return response()->json([
        //         'errors' => [
        //             'user_id' => ['This user is already assigned to another branch.']
        //         ]
        //     ]);
        // }

        // Step 3: Save or update logic
        if (!empty($editId)) {
            $user = User::find($editId);
            $msg = [
                'success' => 'User Updated Successfully',
                'reload' => true
            ];
        } else {
            $user = new User();
            $msg = [
                'success' => 'User Created Successfully',
                'redirect' => route('users.index')
            ];
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json($msg);
    }

    /**
     * Display the specified resource.
     */

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function updateRoles(Request $request)
    {
        $user = User::findOrFail($request->edit_id);

        // Assign new roles (by name)
        $user->syncRoles($request->roles ?? []);

        return back()->with('success', 'User roles updated successfully!');
    }

    public function storeOpeningBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        UserOpeningBalance::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'date' => $request->date
            ],
            [
                'amount' => $request->amount,
                'note' => $request->note,
                'created_by' => auth()->id()
            ]
        );

        return back()->with('success', 'Opening Balance updated successfully!');
    }
}
