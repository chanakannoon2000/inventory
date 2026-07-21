<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', Carbon::today()->toDateString());
        $to = $request->input('to', Carbon::today()->toDateString());
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
            'catRev',
            'cancelledCount',
            'canViewCost'
        ));
    }
}
