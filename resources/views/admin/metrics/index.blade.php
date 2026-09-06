@extends('layouts.admin')

@section('content')
    <div
        class="admin-page metrics-page"
        x-data="accountingMetricsDashboard({
            dataUrl: '{{ route('admin.metrics.data') }}',
            initialFrom: '{{ $initialFrom }}',
            initialTo: '{{ $initialTo }}'
        })"
        x-init="init()"
    >
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Métricas</h1>
                <p class="admin-page__subtitle">
                    Analiza el rendimiento de los cajeros por día, semana o mes.
                </p>
            </div>

            <div class="controlled-products-actions">
                <a href="{{ route('admin.accounting.closures.index') }}" class="admin-btn admin-btn--secondary">
                    Ver cierres
                </a>

                <a href="{{ route('admin.accounting.summary') }}" class="admin-btn admin-btn--secondary">
                    Resumen diario
                </a>
            </div>
        </div>

        <template x-if="error">
            <div class="admin-alert admin-alert--error" x-text="error"></div>
        </template>

        <div class="admin-card metrics-filter-card">
            <div class="metrics-filters">
                <div class="admin-form__group">
                    <label class="admin-form__label">Periodo</label>
                    <select x-model="period" @change="handlePeriodChange()" class="admin-form__input-text">
                        <option value="today">Hoy</option>
                        <option value="week">Esta semana</option>
                        <option value="month">Este mes</option>
                        <option value="last_month">Mes anterior</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label">Desde</label>
                    <input
                        type="date"
                        x-model="from"
                        @change="period = 'custom'; loadMetrics()"
                        class="admin-form__input-text"
                    >
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label">Hasta</label>
                    <input
                        type="date"
                        x-model="to"
                        @change="period = 'custom'; loadMetrics()"
                        class="admin-form__input-text"
                    >
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label">Cajero</label>
                    <select x-model="cashierId" @change="loadMetrics()" class="admin-form__input-text">
                        <option value="all">Todos los cajeros</option>

                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}">
                                {{ $cashier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="metrics-summary-grid">
            <div class="admin-card metrics-stat-card">
                <span>Total facturado</span>
                <strong x-text="formatBs(summary.total_facturado_bs)"></strong>
            </div>

            <div class="admin-card metrics-stat-card">
                <span>Total entregado</span>
                <strong x-text="formatBs(summary.total_entregado_bs)"></strong>
            </div>

            <div class="admin-card metrics-stat-card">
                <span>Diferencia global</span>
                <strong
                    x-text="formatBs(summary.total_difference_bs)"
                    :class="differenceClass(summary.total_difference_bs)"
                ></strong>
            </div>

            <div class="admin-card metrics-stat-card">
                <span>Transacciones</span>
                <strong x-text="summary.total_transactions"></strong>
            </div>

            <div class="admin-card metrics-stat-card">
                <span>Ticket promedio</span>
                <strong x-text="formatBs(summary.average_ticket_bs)"></strong>
            </div>

            <div class="admin-card metrics-stat-card">
                <span>Mejor cajero</span>
                <strong x-text="summary.best_cashier"></strong>
            </div>
        </div>

        <div class="metrics-chart-grid">
            <div class="admin-card metrics-chart-card">
                <h2 class="metrics-card-title">Comparativa por cajero</h2>
                <div x-ref="cashiersChart" class="metrics-chart"></div>
            </div>

            <div class="admin-card metrics-chart-card">
                <h2 class="metrics-card-title">Métodos de pago</h2>
                <div x-ref="methodsChart" class="metrics-chart"></div>
            </div>

            <div class="admin-card metrics-chart-card metrics-chart-card--wide">
                <h2 class="metrics-card-title">Evolución diaria</h2>
                <div x-ref="dailyChart" class="metrics-chart"></div>
            </div>

            <div class="admin-card metrics-chart-card">
                <h2 class="metrics-card-title">Diferencias por cajero</h2>
                <div x-ref="differencesChart" class="metrics-chart"></div>
            </div>
        </div>

        <div class="admin-card metrics-table-card">
            <h2 class="metrics-card-title">Ranking de cajeros</h2>

            <div class="admin-table-wrapper">
                <table class="admin-table accounting-table">
                    <thead>
                        <tr>
                            <th>Cajero</th>
                            <th>Facturado Bs</th>
                            <th>Entregado Bs</th>
                            <th>Diferencia Bs</th>
                            <th>Transacciones</th>
                            <th>Ticket promedio</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="cashierRows.length === 0">
                            <tr>
                                <td colspan="6" class="admin-empty-text">
                                    No hay datos para este rango.
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in cashierRows" :key="row.cashier_id || row.cashier_name">
                            <tr>
                                <td>
                                    <strong x-text="row.cashier_name"></strong>
                                </td>

                                <td x-text="formatBs(row.facturado_bs)"></td>
                                <td x-text="formatBs(row.entregado_bs)"></td>

                                <td
                                    x-text="formatBs(row.difference_bs)"
                                    :class="differenceClass(row.difference_bs)"
                                ></td>

                                <td x-text="row.transactions_count"></td>
                                <td x-text="formatBs(row.average_ticket_bs)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection