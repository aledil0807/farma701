@extends('layouts.admin')

@section('content')
    <div
        class="admin-page accounting-page"
        x-data="accountingDailySummary({
            dataUrl: '{{ route('admin.accounting.summary.data') }}',
            initialDate: '{{ $defaultDate }}'
        })"
        x-init="loadSummary()"
    >
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Resumen diario de contaduría</h1>
                <p class="admin-page__subtitle">
                    Consulta el total global facturado y entregado por método de pago.
                </p>
            </div>

            <div class="controlled-products-actions">
                <a href="{{ route('admin.accounting.closures.index') }}" class="admin-btn admin-btn--secondary">
                    Ver cierres
                </a>

                <a href="{{ route('admin.accounting.closures.create') }}" class="admin-btn admin-btn--primary">
                    Crear cierre
                </a>
            </div>
        </div>

        <div class="admin-card accounting-card">
            <div class="accounting-summary-filter">
                <div class="admin-form__group">
                    <label class="admin-form__label">Seleccionar fecha</label>
                    <input
                        type="date"
                        x-model="selectedDate"
                        @change="loadSummary()"
                        class="admin-form__input-text"
                    >
                </div>

                
            </div>
        </div>

        <template x-if="error">
            <div class="admin-alert admin-alert--error" x-text="error"></div>
        </template>

        <div class="admin-card accounting-card">
            <div class="accounting-summary-heading">
                <div>
                    <h2 class="accounting-card-title">
                        Resumen global del día
                    </h2>

                    <p class="accounting-summary-date" x-text="formattedDate"></p>
                </div>

                <span class="accounting-status accounting-status--closed">
                    <span x-text="closuresCount"></span>
                    <span x-text="closuresCount === 1 ? ' cierre' : ' cierres'"></span>
                </span>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table accounting-table">
                    <thead>
                        <tr>
                            <th>Método de pago</th>
                            <th>Facturado Bs</th>
                            <th>Entregado Bs</th>
                            <th>Diferencia Bs</th>
                            <th>Transacciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="row in summaryRows" :key="row.payment_method_id">
                            <tr>
                                <td>
                                    <strong x-text="row.payment_method_name"></strong>
                                </td>

                                <td x-text="formatBs(row.facturado_bs)"></td>

                                <td x-text="formatBs(row.entregado_bs)"></td>

                                <td
                                    x-text="formatBs(row.difference_bs)"
                                    :class="differenceClass(row.difference_bs)"
                                ></td>

                                <td x-text="row.transactions_count"></td>
                            </tr>
                        </template>
                    </tbody>

                    <tfoot>
                        <tr>
                            <th>Total general</th>
                            <th x-text="formatBs(totals.facturado_bs)"></th>
                            <th x-text="formatBs(totals.entregado_bs)"></th>
                            <th
                                x-text="formatBs(totals.difference_bs)"
                                :class="differenceClass(totals.difference_bs)"
                            ></th>
                            <th x-text="totals.transactions_count"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="admin-card accounting-card" :class="{ 'accounting-card--loading': loading }">
            <h2 class="accounting-card-title">
                Cierres incluidos en este resumen
            </h2>

            <div class="admin-table-wrapper">
                <table class="admin-table accounting-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Turno</th>
                            <th>Facturado Bs</th>
                            <th>Entregado Bs</th>
                            <th>Diferencia Bs</th>
                            <th>Transacciones</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="closureRows.length === 0">
                            <tr>
                                <td colspan="8" class="admin-empty-text">
                                    No hay cierres registrados para esta fecha.
                                </td>
                            </tr>
                        </template>

                        <template x-for="closure in closureRows" :key="closure.id">
                            <tr>
                                <td x-text="closure.employee_name"></td>
                                <td x-text="closure.shift"></td>
                                <td x-text="formatBs(closure.facturado_bs)"></td>
                                <td x-text="formatBs(closure.entregado_bs)"></td>

                                <td
                                    x-text="formatBs(closure.difference_bs)"
                                    :class="differenceClass(closure.difference_bs)"
                                ></td>

                                <td x-text="closure.transactions_count"></td>

                                <td>
                                    <span
                                        class="accounting-status"
                                        :class="'accounting-status--' + closure.status"
                                        x-text="closure.status_label"
                                    ></span>
                                </td>

                                <td>
                                    <a
                                        :href="closure.show_url"
                                        class="admin-btn admin-btn--secondary"
                                    >
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection