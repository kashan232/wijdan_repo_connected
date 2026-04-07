<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class SubcategoryController extends Controller
{
    
    public function index()
    {
        $category = Category::get();
      $subcategory = Subcategory::with('category')->get();
      return  view("admin_panel.subcategory.index",compact('subcategory','category'));


    }

    public function store(request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:subcategories,name,'.$request->edit_id,
            'category_id' => 'required'.$request->edit_id,
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }


        if($request->has('edit_id') && $request->edit_id != '' || $request->edit_id != null ){
            $Company = Subcategory::find($request->edit_id);
            $msg = [
                'success' => 'Subcategory Updated Successfully',
                'reload' => true
            ];
        }
        else{
            $Company = new Subcategory();
            $msg = [
                'success' => 'Subcategory Created Successfully',
                'redirect' => route('subcategory.home')
            ];
        }
        $Company->name = $request->name;
        $Company->category_id = $request->category_id;
        $Company->save();

        return response()->json($msg);
    }

    public function delete($id)
    {

        $company = Subcategory::find($id);
        if ($company) {
            $company->delete();
            $msg = [
                'success' => 'Subcategory Deleted Successfully',
                'reload' =>  route('subcategory.home'),
            ];
        } else {
            $msg = ['error' => 'Subcategory Not Found'];
        }
        return response()->json($msg);
    }
}
