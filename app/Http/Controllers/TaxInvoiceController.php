<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Http\Request;

class TaxInvoiceController extends Controller
{
    public function show(Sale $sale)
    {
        $sale->load(['items.product.unit', 'user']);
        $settings = Setting::current();

        return view('invoices.tax', compact('sale', 'settings'));
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items', 'user']);
        $settings = Setting::current();

        return view('invoices.receipt', compact('sale', 'settings'));
    }

    public function updateCustomer(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_tax_id' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:1000',
            'customer_phone' => 'nullable|string|max:50',
        ]);

        $sale->update($data);

        return redirect()->route('invoices.tax', $sale)->with('success', 'บันทึกข้อมูลลูกค้าแล้ว');
    }
}
