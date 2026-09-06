<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingClosure;
use App\Models\AccountingPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Cashier;


class AccountingClosureController extends Controller
{
    private array $statuses = [
        'open',
        'closed',
    ];

    public function index()
    {
        $closures = AccountingClosure::query()
            ->with(['paymentTotals', 'cashier'])
            ->orderByDesc('closure_date')
            ->orderByDesc('id')
            ->paginate(12);

        return view('admin.accounting.closures.index', compact('closures'));
    }

    public function create()
    {
        $paymentMethods = AccountingPaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $cashiers = Cashier::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.accounting.closures.create', compact('paymentMethods', 'cashiers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateClosure($request);

        $paymentMethods = AccountingPaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $closure = DB::transaction(function () use ($data, $paymentMethods) {
            $cashier = Cashier::find($data['cashier_id']);

            $closure = AccountingClosure::create([
                'cashier_id' => $cashier?->id,
                'closure_date' => $data['closure_date'],
                'employee_name' => $cashier?->name,
                'shift' => $data['shift'] ?? null,
                'status' => $data['status'] ?? 'open',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($paymentMethods as $paymentMethod) {
                $methodData = $data['payment_totals'][$paymentMethod->id] ?? [];

                $closure->paymentTotals()->create([
                    'accounting_payment_method_id' => $paymentMethod->id,
                    'facturado_bs' => $this->parseBsAmount($methodData['facturado_bs'] ?? 0),
                    'entregado_bs' => $this->parseBsAmount($methodData['entregado_bs'] ?? 0),
                    'transactions_count' => max(0, (int) ($methodData['transactions_count'] ?? 0)),
                ]);
            }

            return $closure;
        });

        return redirect()
            ->route('admin.accounting.closures.show', $closure)
            ->with('success', 'Cierre de caja creado correctamente.');
    }

    public function show(AccountingClosure $accountingClosure)
    {
        $accountingClosure->load(['paymentTotals.paymentMethod', 'cashier']);

        $paymentMethods = AccountingPaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $cashiers = Cashier::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $totalsByMethod = $accountingClosure->paymentTotals
            ->keyBy('accounting_payment_method_id');

        return view('admin.accounting.closures.show', compact(
            'accountingClosure',
            'paymentMethods',
            'cashiers',
            'totalsByMethod'
        ));
    }

    public function update(Request $request, AccountingClosure $accountingClosure)
    {
        $data = $this->validateClosure($request);

        $paymentMethods = AccountingPaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        DB::transaction(function () use ($data, $paymentMethods, $accountingClosure) {
            $cashier = Cashier::find($data['cashier_id']);

            $accountingClosure->update([
                'cashier_id' => $cashier?->id,
                'closure_date' => $data['closure_date'],
                'employee_name' => $cashier?->name,
                'shift' => $data['shift'] ?? null,
                'status' => $data['status'] ?? 'open',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($paymentMethods as $paymentMethod) {
                $methodData = $data['payment_totals'][$paymentMethod->id] ?? [];

                $accountingClosure->paymentTotals()->updateOrCreate(
                    [
                        'accounting_payment_method_id' => $paymentMethod->id,
                    ],
                    [
                        'facturado_bs' => $this->parseBsAmount($methodData['facturado_bs'] ?? 0),
                        'entregado_bs' => $this->parseBsAmount($methodData['entregado_bs'] ?? 0),
                        'transactions_count' => max(0, (int) ($methodData['transactions_count'] ?? 0)),
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.accounting.closures.show', $accountingClosure)
            ->with('success', 'Cierre de caja actualizado correctamente.');
    }

    public function destroy(AccountingClosure $accountingClosure)
    {
        $accountingClosure->delete();

        return redirect()
            ->route('admin.accounting.closures.index')
            ->with('success', 'Cierre de caja eliminado correctamente.');
    }

    public function summaryData(Request $request)
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $selectedDate = $data['date'] ?? now()->toDateString();

        $paymentMethods = AccountingPaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $closures = AccountingClosure::query()
            ->with(['paymentTotals', 'cashier'])
            ->whereDate('closure_date', $selectedDate)
            ->orderBy('employee_name')
            ->orderBy('id')
            ->get();

        $aggregates = DB::table('accounting_closure_payment_totals as totals')
            ->join('accounting_closures as closures', 'closures.id', '=', 'totals.accounting_closure_id')
            ->whereDate('closures.closure_date', $selectedDate)
            ->select(
                'totals.accounting_payment_method_id',
                DB::raw('SUM(totals.facturado_bs) as facturado_bs'),
                DB::raw('SUM(totals.entregado_bs) as entregado_bs'),
                DB::raw('SUM(totals.transactions_count) as transactions_count')
            )
            ->groupBy('totals.accounting_payment_method_id')
            ->get()
            ->keyBy('accounting_payment_method_id');

        $summaryRows = $paymentMethods->map(function ($paymentMethod) use ($aggregates) {
            $aggregate = $aggregates->get($paymentMethod->id);

            $facturadoBs = (float) ($aggregate->facturado_bs ?? 0);
            $entregadoBs = (float) ($aggregate->entregado_bs ?? 0);
            $transactionsCount = (int) ($aggregate->transactions_count ?? 0);

            return [
                'payment_method_id' => $paymentMethod->id,
                'payment_method_name' => $paymentMethod->name,
                'facturado_bs' => $facturadoBs,
                'entregado_bs' => $entregadoBs,
                'difference_bs' => $entregadoBs - $facturadoBs,
                'transactions_count' => $transactionsCount,
            ];
        })->values();

        $closureRows = $closures->map(function ($closure) {
            $facturadoBs = (float) $closure->paymentTotals->sum('facturado_bs');
            $entregadoBs = (float) $closure->paymentTotals->sum('entregado_bs');
            $transactionsCount = (int) $closure->paymentTotals->sum('transactions_count');

            return [
                'id' => $closure->id,
                'employee_name' => $closure->cashier_display_name,
                'shift' => $closure->shift ?: '—',
                'status' => $closure->status,
                'status_label' => $closure->status === 'closed' ? 'Cerrado' : 'Abierto',
                'facturado_bs' => $facturadoBs,
                'entregado_bs' => $entregadoBs,
                'difference_bs' => $entregadoBs - $facturadoBs,
                'transactions_count' => $transactionsCount,
                'show_url' => route('admin.accounting.closures.show', $closure),
            ];
        })->values();

        $totalFacturadoBs = (float) $summaryRows->sum('facturado_bs');
        $totalEntregadoBs = (float) $summaryRows->sum('entregado_bs');

        return response()->json([
            'date' => $selectedDate,
            'closures_count' => $closures->count(),
            'summary_rows' => $summaryRows,
            'closure_rows' => $closureRows,
            'totals' => [
                'facturado_bs' => $totalFacturadoBs,
                'entregado_bs' => $totalEntregadoBs,
                'difference_bs' => $totalEntregadoBs - $totalFacturadoBs,
                'transactions_count' => (int) $summaryRows->sum('transactions_count'),
            ],
        ]);
    }

    public function summary()
    {
        return view('admin.accounting.summary', [
            'defaultDate' => now()->toDateString(),
        ]);
    }

    private function validateClosure(Request $request): array
    {
        return $request->validate([
            'cashier_id' => ['required', 'exists:cashiers,id'],
            'closure_date' => ['required', 'date'],
            'employee_name' => ['nullable', 'string', 'max:255'],
            'shift' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in($this->statuses)],
            'notes' => ['nullable', 'string'],

            'payment_totals' => ['nullable', 'array'],
            'payment_totals.*.facturado_bs' => ['nullable', 'string', 'max:50'],
            'payment_totals.*.entregado_bs' => ['nullable', 'string', 'max:50'],
            'payment_totals.*.transactions_count' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function parseBsAmount($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '0.00';
        }

        $value = str_replace(['Bs.', 'Bs', 'bs.', 'bs', ' '], '', $value);

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($hasComma) {
            $value = str_replace(',', '.', $value);
        }

        $value = preg_replace('/[^0-9.-]/', '', $value);

        if (!is_numeric($value)) {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }
}