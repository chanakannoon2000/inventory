<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount('products')->orderBy('name')->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
        ]);

        Supplier::create($data);

        return back()->with('success', 'เพิ่มผู้จำหน่ายสำเร็จ');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
        ]);

        $supplier->update($data);

        return back()->with('success', 'แก้ไขผู้จำหน่ายสำเร็จ');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->products()->exists()) {
            return back()->with('error', 'ไม่สามารถลบได้: มีสินค้าอ้างอิงอยู่');
        }

        $supplier->delete();

        return back()->with('success', 'ลบผู้จำหน่ายแล้ว');
    }
}
