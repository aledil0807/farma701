<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ControlledProductReport;
use App\Models\CustomerProductRequest;
use App\Models\Laboratory;
use App\Models\Product;
use App\Models\Quote;
use App\Models\WebOrder;
use App\Models\WebOrderLaboratoryMetric;
use App\Services\ExchangeRateService;

class DashboardController extends Controller
{
    public function index(ExchangeRateService $exchangeRateService)
    {
        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Throwable $e) {
            $exchangeRate = null;
        }

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfDay();

        $chartFrom = now()->subDays(179)->startOfDay();
        $chartTo = now()->endOfDay();

        $webOrdersToday = WebOrder::query()
            ->whereBetween('ordered_at', [$todayStart, $todayEnd])
            ->count();

        $webOrdersMonth = WebOrder::query()
            ->whereBetween('ordered_at', [$monthStart, $monthEnd])
            ->count();

        $webUnitsMonth = WebOrder::query()
            ->whereBetween('ordered_at', [$monthStart, $monthEnd])
            ->sum('units_count');

        $rawWebSalesByLaboratory = WebOrderLaboratoryMetric::query()
            ->join('web_orders', 'web_orders.id', '=', 'web_order_laboratory_metrics.web_order_id')
            ->whereBetween('web_orders.ordered_at', [$monthStart, $monthEnd])
            ->select('web_order_laboratory_metrics.laboratory_name')
            ->selectRaw('COUNT(DISTINCT web_order_laboratory_metrics.web_order_id) as orders_count')
            ->selectRaw('SUM(web_order_laboratory_metrics.products_count) as products_count')
            ->selectRaw('SUM(web_order_laboratory_metrics.units_count) as units_count')
            ->selectRaw('SUM(web_order_laboratory_metrics.total_usd) as total_usd')
            ->selectRaw('SUM(web_order_laboratory_metrics.total_bs) as total_bs')
            ->groupBy('web_order_laboratory_metrics.laboratory_name')
            ->orderByDesc('units_count')
            ->get();

        $maxLabUnits = max(1, (int) $rawWebSalesByLaboratory->max('units_count'));

        $webSalesByLaboratory = $rawWebSalesByLaboratory
            ->map(function ($row) use ($maxLabUnits) {
                return (object) [
                    'laboratory_name' => $row->laboratory_name,
                    'orders_count' => (int) $row->orders_count,
                    'products_count' => (int) $row->products_count,
                    'units_count' => (int) $row->units_count,
                    'total_usd' => (float) $row->total_usd,
                    'total_bs' => (float) $row->total_bs,
                    'bar_percent' => round(((int) $row->units_count / $maxLabUnits) * 100, 2),
                ];
            })
            ->values();

        $topWebLaboratory = $webSalesByLaboratory->first();

        $dailyOrders = WebOrder::query()
            ->whereBetween('ordered_at', [$chartFrom, $chartTo])
            ->selectRaw('DATE(ordered_at) as order_date')
            ->selectRaw('COUNT(*) as orders_count')
            ->groupByRaw('DATE(ordered_at)')
            ->orderByRaw('DATE(ordered_at)')
            ->get()
            ->keyBy('order_date');

        $maxDailyOrders = max(1, (int) $dailyOrders->max('orders_count'));

        $webOrdersByDay = collect();

        $cursor = $chartFrom->copy();

        while ($cursor->lte($chartTo)) {
            $dateKey = $cursor->toDateString();
            $row = $dailyOrders->get($dateKey);
            $ordersCount = (int) ($row->orders_count ?? 0);

            $webOrdersByDay->push((object) [
                'date' => $dateKey,
                'label' => $cursor->format('d/m'),
                'orders_count' => $ordersCount,
                'bar_percent' => round(($ordersCount / $maxDailyOrders) * 100, 2),
            ]);

            $cursor->addDay();
        }

        $webOrdersByDayChart = $webOrdersByDay
            ->map(function ($day) {
                return [
                    'date' => $day->date,
                    'label' => $day->label,
                    'orders_count' => (int) $day->orders_count,
                ];
            })
            ->values();

        $webSalesByLaboratoryChart = $webSalesByLaboratory
            ->map(function ($laboratory) {
                return [
                    'laboratory_name' => $laboratory->laboratory_name,
                    'orders_count' => (int) $laboratory->orders_count,
                    'products_count' => (int) $laboratory->products_count,
                    'units_count' => (int) $laboratory->units_count,
                    'total_usd' => (float) $laboratory->total_usd,
                    'total_bs' => (float) $laboratory->total_bs,
                ];
            })
            ->values();

        $stats = [
            'exchange_rate' => $exchangeRate,

            'active_products' => Product::query()
                ->where('is_active', true)
                ->count(),

            'visible_labs' => Laboratory::visible()
                ->count(),

            'monthly_products' => Product::query()
                ->where('is_monthly_product', true)
                ->where('is_active', true)
                ->count(),

            'active_banners' => Banner::query()
                ->where('is_active', true)
                ->count(),

            'quotes_count' => Quote::query()
                ->count(),

            'controlled_reports_count' => ControlledProductReport::query()
                ->count(),

            'web_orders_today' => $webOrdersToday,
            'web_orders_month' => $webOrdersMonth,
            'web_units_month' => $webUnitsMonth,
            'web_top_laboratory' => $topWebLaboratory?->laboratory_name ?? 'Sin datos',
        ];

        $latestQuotes = Quote::query()
            ->latest()
            ->limit(5)
            ->get();

        $latestControlledReports = ControlledProductReport::query()
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        $lowStockProducts = Product::query()
            ->with('laboratory')
            ->where('is_active', true)
            ->where('cantidad', '>', 0)
            ->where('cantidad', '<=', 4)
            ->orderBy('cantidad')
            ->limit(6)
            ->get();

        $productRequests = CustomerProductRequest::query()
            ->whereNull('sent_at')
            ->latest()
            ->get()
            ->map(function ($productRequest) {
                $productRequest->has_active_match = Product::query()
                    ->where('is_active', true)
                    ->searchByTerms($productRequest->searched_product)
                    ->exists();

                return $productRequest;
            })
            ->sort(function ($a, $b) {
                $availableComparison = (int) $b->has_active_match <=> (int) $a->has_active_match;

                if ($availableComparison !== 0) {
                    return $availableComparison;
                }

                return $b->created_at <=> $a->created_at;
            })
            ->take(8)
            ->values();

        return view('admin.dashboard', compact(
            'stats',
            'latestQuotes',
            'latestControlledReports',
            'lowStockProducts',
            'productRequests',
            'webOrdersByDay',
            'webSalesByLaboratory',
            'webOrdersByDayChart',
            'webSalesByLaboratoryChart'
        ));
    }
}