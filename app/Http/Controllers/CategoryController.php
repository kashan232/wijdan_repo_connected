<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class CategoryController extends Controller
{
    
    public function index()
    {
        // $userId = Auth::id();
      $category = Category::get();
      return  view("admin_panel.category.index",compact('category'));


    }

    public function store(request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:categories,name,'.$request->edit_id,
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }


        if($request->has('edit_id') && $request->edit_id != '' || $request->edit_id != null ){
            $Company = Category::find($request->edit_id);
            $msg = [
                'success' => 'Category Updated Successfully',
                'reload' => true
            ];
        }
        else{
            $Company = new Category();
            $msg = [
                'success' => 'Category Created Successfully',
                'redirect' => $request->redirect_url ?? route('Category.home')
            ];
        }
        $Company->name = $request->name;
        $Company->save();

        return response()->json($msg);
    }

    public function delete($id)
    {

        $company = Category::find($id);
        if ($company) {
            $company->delete();
            $msg = [
                'success' => 'Category Deleted Successfully',
                'reload' =>  route('Category.home'),
            ];
        } else {
            $msg = ['error' => 'Category Not Found'];
        }
        return response()->json($msg);
    }
   
     
}
