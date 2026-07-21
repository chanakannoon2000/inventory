<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'barcode_prefix' => 'required|string|max:2|regex:/^[A-Za-z]$/',
            'icon' => 'nullable|string|max:16',
            'color' => 'nullable|string|max:20',
        ], [
            'barcode_prefix.required' => 'กรุณาใส่ตัวอักษรนำหน้าบาร์โค้ด',
            'barcode_prefix.regex' => 'ตัวอักษรนำหน้าต้องเป็น A-Z ตัวเดียว',
        ]);
        $data['barcode_prefix'] = strtoupper($data['barcode_prefix']);

        Category::create($data);

        return back()->with('success', 'เพิ่มหมวดหมู่สำเร็จ');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'barcode_prefix' => 'required|string|max:2|regex:/^[A-Za-z]$/',
            'icon' => 'nullable|string|max:16',
            'color' => 'nullable|string|max:20',
        ], [
            'barcode_prefix.required' => 'กรุณาใส่ตัวอักษรนำหน้าบาร์โค้ด',
            'barcode_prefix.regex' => 'ตัวอักษรนำหน้าต้องเป็น A-Z ตัวเดียว',
        ]);
        $data['barcode_prefix'] = strtoupper($data['barcode_prefix']);

        $category->update($data);

        return back()->with('success', 'แก้ไขหมวดหมู่สำเร็จ');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'ไม่สามารถลบได้: มีสินค้าใช้งานอยู่');
        }

        $category->delete();

        return back()->with('success', 'ลบหมวดหมู่แล้ว');
    }
}
