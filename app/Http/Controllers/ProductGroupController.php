<?php

namespace App\Http\Controllers;

use App\Models\ProductGroup;
use Illuminate\Http\Request;

class ProductGroupController extends Controller
{
    public function index()
    {
        $groups = ProductGroup::withCount('products')->orderBy('name')->get();

        return view('product_groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:product_groups,name',
        ], [
            'name.required' => 'กรุณากรอกชื่อกลุ่ม',
            'name.unique' => 'ชื่อกลุ่มนี้มีอยู่แล้ว',
        ]);

        ProductGroup::create($data);

        return back()->with('success', 'เพิ่มกลุ่มสินค้าสำเร็จ');
    }

    public function update(Request $request, ProductGroup $productGroup)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:product_groups,name,'.$productGroup->id,
        ], [
            'name.required' => 'กรุณากรอกชื่อกลุ่ม',
            'name.unique' => 'ชื่อกลุ่มนี้มีอยู่แล้ว',
        ]);

        $productGroup->update($data);

        return back()->with('success', 'แก้ไขกลุ่มสินค้าสำเร็จ');
    }

    public function destroy(ProductGroup $productGroup)
    {
        if ($productGroup->products()->exists()) {
            return back()->with('error', 'ไม่สามารถลบได้: มีสินค้าในกลุ่มนี้อยู่');
        }

        $productGroup->delete();

        return back()->with('success', 'ลบกลุ่มสินค้าแล้ว');
    }
}
