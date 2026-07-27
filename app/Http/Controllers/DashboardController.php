<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'day');
        if (! in_array($period, ['day', 'month', 'year'], true)) {
            $period = 'day';
        }

        [$from, $to, $label, $dateValue, $monthValue, $yearValue] = $this->resolveRange($request, $period);

        $periodSales = Sale::with('items')
            ->active()
            ->whereBetween('sold_at', [$from, $to])
            ->get();

        $periodRevenue = (float) $periodSales->sum('total');
        $periodCost = (float) $periodSales->sum(fn ($s) => $s->costTotal());
        $grossProfit = $periodRevenue - $periodCost;
        $periodExpenses = (float) Expense::query()
            ->whereBetween('spent_at', [$from, $to])
            ->sum('amount');
        $netAfterExpenses = $grossProfit - $periodExpenses;

        $productCount = Product::count();
        $lowStock = Product::with(['unit', 'supplier'])->lowStock()->orderBy('stock')->get();
        $overStockCount = Product::overStock()->count();

        [$chartLabels, $chartData, $chartTitle] = $this->buildChart($period, $from, $to);

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->whereNull('sales.cancelled_at')
            ->select('sale_items.product_id', 'sale_items.product_name', DB::raw('SUM(sale_items.qty) as total_qty'))
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'period' => $period,
            'periodLabel' => $label,
            'dateValue' => $dateValue,
            'monthValue' => $monthValue,
            'yearValue' => $yearValue,
            'years' => range((int) now()->year, (int) now()->year - 5),
            'periodRevenue' => $periodRevenue,
            'periodCost' => $periodCost,
            'grossProfit' => $grossProfit,
            'periodExpenses' => $periodExpenses,
            'netAfterExpenses' => $netAfterExpenses,
            'periodSales' => $periodSales,
            'productCount' => $productCount,
            'lowStock' => $lowStock,
            'overStockCount' => $overStockCount,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'chartTitle' => $chartTitle,
            'topProducts' => $topProducts,
            'canViewCost' => auth()->user()?->canViewCost(),
            'shopName' => Setting::current()->shop_name,
        ]);
    }

    /**
     * @return array{0:Carbon,1:Carbon,2:string,3:string,4:string,5:int}
     */
    private function resolveRange(Request $request, string $period): array
    {
        if ($period === 'month') {
            $monthValue = $request->input('month', now()->format('Y-m'));
            try {
                $start = Carbon::createFromFormat('Y-m', $monthValue)->startOfMonth();
            } catch (\Throwable) {
                $start = now()->startOfMonth();
                $monthValue = $start->format('Y-m');
            }

            return [
                $start->copy(),
                $start->copy()->endOfMonth(),
                'เดือน '.$start->translatedFormat('F Y'),
                now()->toDateString(),
                $monthValue,
                (int) $start->year,
            ];
        }

        if ($period === 'year') {
            $yearValue = (int) $request->input('year', now()->year);
            if ($yearValue < 2000 || $yearValue > 2100) {
                $yearValue = (int) now()->year;
            }
            $start = Carbon::create($yearValue, 1, 1)->startOfDay();

            return [
                $start->copy(),
                $start->copy()->endOfYear(),
                'ปี '.$yearValue,
                now()->toDateString(),
                now()->format('Y-m'),
                $yearValue,
            ];
        }

        $dateValue = $request->input('date', now()->toDateString());
        try {
            $day = Carbon::parse($dateValue)->startOfDay();
        } catch (\Throwable) {
            $day = now()->startOfDay();
            $dateValue = $day->toDateString();
        }

        return [
            $day->copy()->startOfDay(),
            $day->copy()->endOfDay(),
            'วันที่ '.$day->translatedFormat('d M Y'),
            $dateValue,
            $day->format('Y-m'),
            (int) $day->year,
        ];
    }

    /**
     * @return array{0:list<string>,1:list<float>,2:string}
     */
    private function buildChart(string $period, Carbon $from, Carbon $to): array
    {
        $labels = [];
        $data = [];

        if ($period === 'year') {
            for ($m = 1; $m <= 12; $m++) {
                $monthStart = $from->copy()->month($m)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();
                $labels[] = $monthStart->translatedFormat('M');
                $data[] = (float) Sale::active()->whereBetween('sold_at', [$monthStart, $monthEnd])->sum('total');
            }

            return [$labels, $data, 'ยอดขายรายเดือน ปี '.$from->year];
        }

        if ($period === 'month') {
            $cursor = $from->copy()->startOfDay();
            $end = $to->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $labels[] = $cursor->format('d');
                $data[] = (float) Sale::active()->whereDate('sold_at', $cursor)->sum('total');
                $cursor->addDay();
            }

            return [$labels, $data, 'ยอดขายรายวัน '.$from->translatedFormat('F Y')];
        }

        // day → แสดง 7 วันย้อนหลังถึงวันที่เลือก
        for ($i = 6; $i >= 0; $i--) {
            $day = $to->copy()->startOfDay()->subDays($i);
            $labels[] = $day->translatedFormat('d/m');
            $data[] = (float) Sale::active()->whereDate('sold_at', $day)->sum('total');
        }

        return [$labels, $data, 'ยอดขาย 7 วันถึงวันที่เลือก'];
    }
}
