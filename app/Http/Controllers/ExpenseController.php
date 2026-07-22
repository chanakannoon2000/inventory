<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', Carbon::today()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::today()->toDateString());
        $category = $request->input('category');

        $base = Expense::query()
            ->whereDate('spent_at', '>=', $from)
            ->whereDate('spent_at', '<=', $to);

        if ($category) {
            $base->where('category', $category);
        }

        $totalAmount = (float) (clone $base)->sum('amount');

        $expenses = (clone $base)
            ->with('user')
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $byCategory = Expense::query()
            ->whereDate('spent_at', '>=', $from)
            ->whereDate('spent_at', '<=', $to)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('expenses.index', [
            'expenses' => $expenses,
            'from' => $from,
            'to' => $to,
            'category' => $category,
            'totalAmount' => $totalAmount,
            'byCategory' => $byCategory,
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = auth()->id();

        Expense::create($data);

        return back()->with('success', 'บันทึกเบิกรายจ่ายแล้ว');
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return back()->with('success', 'แก้ไขรายจ่ายแล้ว');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'ลบรายจ่ายแล้ว');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'spent_at' => 'required|date',
            'category' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer',
            'paid_by_name' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ], [
            'title.required' => 'กรุณาระบุรายการ เช่น ซื้อกับข้าว',
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.min' => 'จำนวนเงินต้องมากกว่า 0',
            'category.required' => 'กรุณาเลือกหมวดรายจ่าย',
        ]);

        $data['amount'] = (float) $data['amount'];
        $data['paid_by_name'] = isset($data['paid_by_name']) ? trim((string) $data['paid_by_name']) ?: null : null;
        $data['note'] = isset($data['note']) ? trim((string) $data['note']) ?: null : null;

        return $data;
    }
}
