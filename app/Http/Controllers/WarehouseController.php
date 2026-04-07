<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;

class WarehouseController extends Controller
{
    // VendorController.php aur WarehouseController.php same hoga
public function index() {
    $warehouses = Warehouse::with('user')->get(); // ya $warehouses = Warehouse::all();
    return view('admin_panel.warehouses.index', compact('warehouses')); // ya warehouses.index
}

public function store(Request $request) {
    if ($request->id) {
        Warehouse::findOrFail($request->id)->update($request->all());
    } else {
        Warehouse::create($request->all());
    }
    return back()->with('success', 'Saved Successfully');
}

public function delete($id) {
    Warehouse::findOrFail($id)->delete();
    return back()->with('success', 'Deleted Successfully');
}

}
