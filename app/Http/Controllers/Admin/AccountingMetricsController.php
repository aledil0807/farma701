<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingClosure;
use App\Models\Cashier;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountingMetricsController extends Controller
{
    public function index()
    {
        $cashiers = Cashier::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.metrics.index', [
            'cashiers' => $cashiers,
            'initialFrom' => now()->startOfMonth()->toDateString(),
            'initialTo' => now()->toDateString(),
        ]);
    }

    public function data(Request $request)
    {
        $data = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'week', 'month', 'last_month', 'custom'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'cashier_id' => ['nullable'],
        ]);

        [$from, $to] = $this->resolveDateRange($data);

        if ($from->diffInDays($to) > 370) {
            return response()->json([
                'message' => 'El rango máximo permitido es de 370 días.',
            ], 422);
        }

        $cashierId = $data['cashier_id'] ?? null;

        if ($cashierId === 'all' || $cashierId === '') {
            $cashierId = null;
        }

        $closuresCount = AccountingClosure::query()
            ->whereDate('closure_date', '>=', $from->toDateString())
            ->whereDate('closure_date', '<=', $to->toDateString())
            ->when($cashierId, fn($query) => $query->where('cashier_id', $cashierId))
            ->count();

        $cashierRows = $this->cashierRows($from, $to, $cashierId);
        $dailyRows = $this->dailyRows($from, $to, $cashierId);
        $paymentMethodRows = $this->paymentMethodRows($from, $to, $cashierId);

        $totalFacturadoBs = (float) $cashierRows->sum('facturado_bs');
        $totalEntregadoBs = (float) $cashierRows->sum('entregado_bs');
        $totalTransactions = (int) $cashierRows->sum('transactions_count');
        $totalDifferenceBs = $totalEntregadoBs - $totalFacturadoBs;

        $bestCashier = $cashierRows
            ->sortByDesc('facturado_bs')
            ->first();

        return response()->json([
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'label' => $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'),
            ],
            'summary' => [
                'closures_count' => $closuresCount,
                'total_facturado_bs' => $totalFacturadoBs,
                'total_entregado_bs' => $totalEntregadoBs,
                'total_difference_bs' => $totalDifferenceBs,
                'total_transactions' => $totalTransactions,
                'average_ticket_bs' => $totalTransactions > 0 ? $totalFacturadoBs / $totalTransactions : 0,
                'best_cashier' => $bestCashier['cashier_name'] ?? '—',
            ],
            'cashier_rows' => $cashierRows->values(),
            'daily_rows' => $dailyRows->values(),
            'payment_method_rows' => $paymentMethodRows->values(),
        ]);
    }

    private function resolveDateRange(array $data): array
    {
        $period = $data['period'] ?? 'month';

        return match ($period) {
            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
            ],
            'week' => [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ],
            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ],
            'custom' => [
                Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay(),
                Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay(),
            ],
            default => [
                now()->startOfMonth(),
                now()->endOfDay(),
            ],
        };
    }

    private function cashierRows(Carbon $from, Carbon $to, ?string $cashierId)
    {
        return DB::table('accounting_closures as closures')
            ->join('accounting_closure_payment_totals as totals', 'totals.accounting_closure_id', '=', 'closures.id')
            ->leftJoin('cashiers', 'cashiers.id', '=', 'closures.cashier_id')
            ->whereDate('closures.closure_date', '>=', $from->toDateString())
            ->whereDate('closures.closure_date', '<=', $to->toDateString())
            ->when($cashierId, fn($query) => $query->where('closures.cashier_id', $cashierId))
            ->select(
                'closures.cashier_id',
                DB::raw("COALESCE(cashiers.name, MIN(closures.employee_name), 'Sin cajero') as cashier_name"),
                DB::raw('SUM(totals.facturado_bs) as facturado_bs'),
                DB::raw('SUM(totals.entregado_bs) as entregado_bs'),
                DB::raw('SUM(totals.transactions_count) as transactions_count')
            )
            ->groupBy('closures.cashier_id', 'cashiers.name')
            ->orderByDesc(DB::raw('SUM(totals.facturado_bs)'))
            ->get()
            ->map(function ($row) {
                $facturadoBs = (float) $row->facturado_bs;
                $entregadoBs = (float) $row->entregado_bs;
                $transactions = (int) $row->transactions_count;

                return [
                    'cashier_id' => $row->cashier_id,
                    'cashier_name' => $row->cashier_name,
                    'facturado_bs' => $facturadoBs,
                    'entregado_bs' => $entregadoBs,
                    'difference_bs' => $entregadoBs - $facturadoBs,
                    'transactions_count' => $transactions,
                    'average_ticket_bs' => $transactions > 0 ? $facturadoBs / $transactions : 0,
                ];
            });
    }

    private function dailyRows(Carbon $from, Carbon $to, ?string $cashierId)
    {
        $rows = DB::table('accounting_closures as closures')
            ->join('accounting_closure_payment_totals as totals', 'totals.accounting_closure_id', '=', 'closures.id')
            ->whereDate('closures.closure_date', '>=', $from->toDateString())
            ->whereDate('closures.closure_date', '<=', $to->toDateString())
            ->when($cashierId, fn($query) => $query->where('closures.cashier_id', $cashierId))
            ->select(
                DB::raw('DATE(closures.closure_date) as closure_date'),
                DB::raw('SUM(totals.facturado_bs) as facturado_bs'),
                DB::raw('SUM(totals.entregado_bs) as entregado_bs'),
                DB::raw('SUM(totals.transactions_count) as transactions_count')
            )
            ->groupBy(DB::raw('DATE(closures.closure_date)'))
            ->orderBy(DB::raw('DATE(closures.closure_date)'))
            ->get()
            ->keyBy('closure_date');

        return collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(function ($date) use ($rows) {
                $key = $date->toDateString();
                $row = $rows->get($key);

                $facturadoBs = (float) ($row->facturado_bs ?? 0);
                $entregadoBs = (float) ($row->entregado_bs ?? 0);

                return [
                    'date' => $key,
                    'date_label' => $date->format('d/m'),
                    'facturado_bs' => $facturadoBs,
                    'entregado_bs' => $entregadoBs,
                    'difference_bs' => $entregadoBs - $facturadoBs,
                    'transactions_count' => (int) ($row->transactions_count ?? 0),
                ];
            });
    }

    private function paymentMethodRows(Carbon $from, Carbon $to, ?string $cashierId)
    {
        return DB::table('accounting_closure_payment_totals as totals')
            ->join('accounting_closures as closures', 'closures.id', '=', 'totals.accounting_closure_id')
            ->join('accounting_payment_methods as methods', 'methods.id', '=', 'totals.accounting_payment_method_id')
            ->whereDate('closures.closure_date', '>=', $from->toDateString())
            ->whereDate('closures.closure_date', '<=', $to->toDateString())
            ->when($cashierId, fn($query) => $query->where('closures.cashier_id', $cashierId))
            ->select(
                'methods.id',
                'methods.name',
                DB::raw('SUM(totals.facturado_bs) as facturado_bs'),
                DB::raw('SUM(totals.entregado_bs) as entregado_bs'),
                DB::raw('SUM(totals.transactions_count) as transactions_count')
            )
            ->groupBy('methods.id', 'methods.name', 'methods.sort_order')
            ->orderBy('methods.sort_order')
            ->orderBy('methods.name')
            ->get()
            ->map(function ($row) {
                $facturadoBs = (float) $row->facturado_bs;
                $entregadoBs = (float) $row->entregado_bs;

                return [
                    'payment_method_id' => $row->id,
                    'payment_method_name' => $row->name,
                    'facturado_bs' => $facturadoBs,
                    'entregado_bs' => $entregadoBs,
                    'difference_bs' => $entregadoBs - $facturadoBs,
                    'transactions_count' => (int) $row->transactions_count,
                ];
            });
    }
}