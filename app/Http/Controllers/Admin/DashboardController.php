<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ControlledProductReport;
use App\Models\Laboratory;
use App\Models\Product;
use App\Models\Quote;
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

        return view('admin.dashboard', compact(
            'stats',
            'latestQuotes',
            'latestControlledReports',
            'lowStockProducts'
        ));
    }
}