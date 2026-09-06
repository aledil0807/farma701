@extends('layouts.admin')

@section('content')
    <div class="admin-page controlled-products-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Productos controlados</h1>
                <p class="admin-page__subtitle">
                    Crea y consulta reportes mensuales de productos controlados.
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">
                {{ session('success') }}
            </div>
        @endif

        <div class="admin-card controlled-products-form-card">
            <h2 class="controlled-products-card-title">Nuevo reporte mensual</h2>

            <form
                method="POST"
                action="{{ route('admin.controlled-products.month.store') }}"
                class="controlled-products-form"
            >
                @csrf

                <div class="controlled-products-form__grid controlled-products-form__grid--report">
                    

                    <div class="admin-form__group">
                        <label class="admin-form__label">Mes del reporte</label>
                        <input
                            type="month"
                            name="report_month"
                            value="{{ old('report_month', now()->format('Y-m')) }}"
                            class="admin-form__input-text"
                            required
                        >
                    </div>


                    <div class="admin-form__group controlled-products-form__button">
                        <button type="submit" class="admin-btn admin-btn--primary">
                            Crear reporte mensual
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="admin-card controlled-products-table-card">
            <h2 class="controlled-products-card-title">Reportes mensuales guardados</h2>

            <div class="admin-table-wrapper">
                <table class="admin-table controlled-products-table">
                    <thead>
                        <tr>
                            <th>Mes</th>

                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($monthlyReports as $monthlyReport)
                            <tr>
                                <td>
                                    <strong>{{ $monthlyReport->month ?: '—' }}</strong>
                                </td>

                                

                                <td>
                                    {{ $monthlyReport->created_at ? $monthlyReport->created_at->format('d/m/Y') : '—' }}
                                </td>

                                <td>
                                    <div class="controlled-products-actions">
                                        <a
                                            href="{{ route('admin.controlled-products.month.show', $monthlyReport->month) }}"
                                            class="admin-btn admin-btn--secondary"
                                        >
                                            Ver/Editar
                                        </a>

                                        <a
                                            href="{{ route('admin.controlled-products.month.pdf', $monthlyReport->month) }}"
                                            class="admin-btn admin-btn--primary"
                                            target="_blank"
                                        >
                                            <i class="fa-solid fa-file-pdf"></i>
                                            Exportar PDF 
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-empty-text">
                                    Todavía no hay reportes mensuales guardados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection