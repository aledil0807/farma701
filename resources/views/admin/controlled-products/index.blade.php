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

            <form method="POST" action="{{ route('admin.controlled-products.store') }}" class="controlled-products-form">
                @csrf

                <div class="controlled-products-form__grid controlled-products-form__grid--report">
                    <div class="admin-form__group">
                        <label class="admin-form__label">Título del reporte</label>
                        <input type="text" name="title" value="{{ old('title') }}" class="admin-form__input-text"
                            placeholder="Ej: Reporte productos controlados">
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Mes del reporte</label>
                        <input type="month" name="report_month" value="{{ old('report_month', now()->format('Y-m')) }}"
                            class="admin-form__input-text" required>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Categoría</label>
                        <input type="text" name="category" value="{{ old('category') }}" class="admin-form__input-text"
                            placeholder="Ej: Psicotrópicos">
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Notas</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="admin-form__input-text"
                            placeholder="Opcional">
                    </div>

                    <div class="admin-form__group controlled-products-form__button">
                        <button type="submit" class="admin-btn admin-btn--primary">
                            Crear reporte
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="admin-card controlled-products-table-card">
            <h2 class="controlled-products-card-title">Reportes guardados</h2>

            <div class="admin-table-wrapper">
                <table class="admin-table controlled-products-table">
                    <thead>
                        <tr>
                            <th>N° reporte</th>
                            <th>Título</th>
                            <th>Mes</th>
                            <th>Categoría</th>
                            <th>Productos</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ $report->report_number }}</td>
                                <td>{{ $report->title ?: 'Reporte de productos controlados' }}</td>
                                <td>{{ $report->report_month ?: '—' }}</td>
                                <td>{{ $report->category ?: '—' }}</td>
                                <td>{{ $report->items_count }}</td>
                                <td>{{ $report->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <div class="controlled-products-actions">
                                        <a href="{{ route('admin.controlled-products.show', $report) }}"
                                            class="admin-btn admin-btn--secondary">
                                            Ver / editar
                                        </a>

                                        <form method="POST" action="{{ route('admin.controlled-products.destroy', $report) }}"
                                            onsubmit="return confirm('¿Eliminar este reporte completo?')">
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
                                <td colspan="7" class="admin-empty-text">
                                    Todavía no hay reportes guardados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="controlled-products-pagination">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
@endsection