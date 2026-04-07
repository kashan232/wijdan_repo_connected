<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Unit;
use Illuminate\Http\Request;

class ManuallController extends Controller
{
    public function category(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name,'.$request->edit_id,
        ]);

        if ($request->has('edit_id') && ! empty($request->edit_id)) {
            $category = Category::findOrFail($request->edit_id);
            $category->name = $request->name;
            $category->save();

            return redirect()
                ->back()
                ->with('success', 'Category Updated Successfully');
        } else {
            $category = new Category;
            $category->name = $request->name;
            $category->save();

            return redirect()
                ->back()
                ->with('success', 'Category created Successfully');
        }
    }

    public function subcategory(Request $request)
    {
        $request->validate([
            'sub_category' => 'required',
            'category_id' => 'required',
        ]);

        if ($request->has('edit_id') && ! empty($request->edit_id)) {
            $subcategory = Subcategory::findOrFail($request->edit_id);
            $subcategory->name = $request->sub_category;
            $subcategory->category_id = $request->category_id;
            $subcategory->save();

            return redirect()->back()->with('success', 'Subcategory Updated Successfully');
        } else {
            $subcategory = new Subcategory;
            $subcategory->name = $request->sub_category;
            $subcategory->category_id = $request->category_id;
            $subcategory->save();

            return redirect()->back()->with('success', 'Subcategory created Successfully');
        }
    }

    public function unit(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        if ($request->has('edit_id') && ! empty($request->edit_id)) {
            $unit = Unit::findOrFail($request->edit_id);
            $unit->name = $request->name;
            $unit->save();

            return redirect()
                ->back()
                ->with('success', 'Unit Updated Successfully');
        } else {
            $unit = new Unit;
            $unit->name = $request->name;
            $unit->save();

            return redirect()
                ->back()
                ->with('success', 'Unit created Successfully');
        }
    }

    public function brand(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        if ($request->has('edit_id') && ! empty($request->edit_id)) {
            $brand = Brand::findOrFail($request->edit_id);
            $brand->name = $request->name;
            $brand->save();

            return redirect()
                ->back()
                ->with('success', 'Brand Updated Successfully');
        } else {
            $brand = new Brand;
            $brand->name = $request->name;
            $brand->save();

            return redirect()
                ->back()
                ->with('success', 'Brand created Successfully');
        }
    }
}
