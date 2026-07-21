<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::withCount('products')->orderBy('name')->get();

        return view('units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Unit::create($data);

        return back()->with('success', 'เพิ่มหน่วยนับสำเร็จ');
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $unit->update($data);

        return back()->with('success', 'แก้ไขหน่วยนับสำเร็จ');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->products()->exists()) {
            return back()->with('error', 'ไม่สามารถลบได้: มีสินค้าใช้งานอยู่');
        }

        $unit->delete();

        return back()->with('success', 'ลบหน่วยนับแล้ว');
    }
}
