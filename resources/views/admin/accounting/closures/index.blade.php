@extends('layouts.admin')

@section('content')
    <div class="admin-page accounting-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Administración</h1>
                
            </div>

            <div class="controlled-products-actions">
                <a href="{{ route('admin.accounting.summary') }}" class="admin-btn admin-btn--secondary">
                    Ver resumen diario
                </a>

                <a href="{{ route('admin.accounting.closures.create') }}" class="admin-btn admin-btn--primary">
                    Crear cierre
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">
                {{ session('success') }}
            </div>
        @endif

        <div class="admin-card accounting-card">
            <h2 class="accounting-card-title">Cierres registrados</h2>

            <div class="admin-table-wrapper">
                <table class="admin-table accounting-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Empleado</th>
                            <th>Turno</th>
                            <th>Facturado Bs</th>
                            <th>Entregado Bs</th>
                            <th>Diferencia Bs</th>
                            <th>Transacciones</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($closures as $closure)
                            @php
                                $totalFacturado = $closure->paymentTotals->sum('facturado_bs');
                                $totalEntregado = $closure->paymentTotals->sum('entregado_bs');
                                $diferencia = $totalEntregado - $totalFacturado;
                                $totalTransactions = $closure->paymentTotals->sum('transactions_count');
                            @endphp

                            <tr>
                                <td>
                                    {{ $closure->closure_date?->format('d/m/Y') }}
                                </td>

                                <td>
                                    {{ $closure->cashier_display_name }}
                                </td>

                                <td>
                                    {{ $closure->shift ?: '—' }}
                                </td>

                                <td>
                                    Bs. {{ number_format($totalFacturado, 2, ',', '.') }}
                                </td>

                                <td>
                                    Bs. {{ number_format($totalEntregado, 2, ',', '.') }}
                                </td>

                                <td
                                    class="{{ $diferencia < 0 ? 'accounting-difference-negative' : ($diferencia > 0 ? 'accounting-difference-positive' : '') }}">
                                    Bs. {{ number_format($diferencia, 2, ',', '.') }}
                                </td>

                                <td>
                                    {{ $totalTransactions }}
                                </td>

                                <td>
                                    <span class="accounting-status accounting-status--{{ $closure->status }}">
                                        {{ $closure->status === 'closed' ? 'Cerrado' : 'Abierto' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="controlled-products-actions">
                                        <a href="{{ route('admin.accounting.closures.show', $closure) }}"
                                            class="admin-btn admin-btn--secondary">
                                            Ver / editar
                                        </a>

                                        <form method="POST" action="{{ route('admin.accounting.closures.destroy', $closure) }}"
                                            onsubmit="return confirm('¿Eliminar este cierre de caja?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="admin-btn admin-btn--danger">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="admin-empty-text">
                                    Todavía no hay cierres registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="controlled-products-pagination">
                {{ $closures->links() }}
            </div>
        </div>
    </div>
@endsection