<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class BrandController extends Controller
{
     public function index()
    {
        // $userId = Auth::id();
      $Brand = Brand::get();
      return  view("admin_panel.brand.index",compact('Brand'));


    }

    public function store(request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:brands,name,'.$request->edit_id,
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }


        if($request->has('edit_id') && $request->edit_id != '' || $request->edit_id != null ){
            $Company = Brand::find($request->edit_id);
            $msg = [
                'success' => 'Brand Updated Successfully',
                'reload' => true
            ];
        }
        else{
            $Company = new Brand();
            $msg = [
                'success' => 'Brand Created Successfully',
                'redirect' => route('Brand.home')
            ];
        }
        $Company->name = $request->name;
        $Company->save();

        return response()->json($msg);
    }

    public function delete($id)
    {

        $company = Brand::find($id);
        if ($company) {
            $company->delete();
            $msg = [
                'success' => 'Brand Deleted Successfully',
                'reload' =>  route('Brand.home'),
            ];
        } else {
            $msg = ['error' => 'Brand Not Found'];
        }
        return response()->json($msg);
    }
}
