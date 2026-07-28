<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request->input('from'), $request->input('to'));
        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $canViewCost = (bool) auth()->user()?->canViewCost();

        $baseQuery = Sale::query()
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to);

        $activeSalesCount = (clone $baseQuery)->whereNull('cancelled_at')->count();
        $cancelledCount = (clone $baseQuery)->whereNotNull('cancelled_at')->count();
        $totalRev = (float) (clone $baseQuery)->whereNull('cancelled_at')->sum('total');

        $totalCost = 0.0;
        if ($canViewCost) {
            $totalCost = (float) (clone $baseQuery)
                ->with('items')
                ->whereNull('cancelled_at')
                ->get()
                ->sum(fn (Sale $s) => $s->costTotal());
        }
        $profit = $totalRev - $totalCost;
        $totalExpenses = (float) Expense::query()
            ->whereDate('spent_at', '>=', $from)
            ->whereDate('spent_at', '<=', $to)
            ->sum('amount');
        $netAfterExpenses = $profit - $totalExpenses;

        $sales = Sale::with('items')
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->orderByDesc('sold_at')
            ->paginate($perPage)
            ->withQueryString();

        $catRev = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereDate('sales.sold_at', '>=', $from)
            ->whereDate('sales.sold_at', '<=', $to)
            ->whereNull('sales.cancelled_at')
            ->select(
                DB::raw("COALESCE(categories.name, 'อื่นๆ') as category_name"),
                DB::raw('SUM(sale_items.qty * sale_items.unit_price) as revenue')
            )
            ->groupBy('category_name')
            ->orderByDesc('revenue')
            ->get();

        return view('reports.index', compact(
            'from',
            'to',
            'perPage',
            'sales',
            'activeSalesCount',
            'totalRev',
            'totalCost',
            'profit',
            'totalExpenses',
            'netAfterExpenses',
            'catRev',
            'cancelledCount',
            'canViewCost'
        ) + ['shopName' => Setting::current()->shop_name]);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveDateRange(?string $from, ?string $to): array
    {
        $today = Carbon::today()->toDateString();

        try {
            $from = $from ? Carbon::parse($from)->toDateString() : $today;
        } catch (\Throwable) {
            $from = $today;
        }

        try {
            $to = $to ? Carbon::parse($to)->toDateString() : $today;
        } catch (\Throwable) {
            $to = $today;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
