@extends('layouts.admin')

@section('content')
    <div class="admin-page controlled-products-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">
                    Reporte mensual de productos controlados
                </h1>

                <p class="admin-page__subtitle">
                    Mes: {{ $month }}
                </p>
            </div>

            <div class="controlled-products-actions">
                <a
                    href="{{ route('admin.controlled-products.month.pdf', $month) }}"
                    class="admin-btn admin-btn--primary"
                    target="_blank"
                >
                    <i class="fa-solid fa-file-pdf"></i>
                    Exportar PDF global
                </a>

                <a
                    href="{{ route('admin.controlled-products.index') }}"
                    class="admin-btn admin-btn--secondary"
                >
                    Volver
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">
                {{ session('success') }}
            </div>
        @endif

        <div class="admin-card controlled-products-table-card">
            <div class="controlled-products-table-head">
                <div>
                    <h2 class="controlled-products-card-title">
                        Categorías del reporte
                    </h2>

                    <p>
                        Administra cada categoría individualmente y luego exporta todas en un solo PDF.
                    </p>
                </div>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table controlled-products-table">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>N° reporte</th>
                            <th>Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($categories as $category)
                            @php
                                $report = $reportsByCategory->get($category);
                            @endphp

                            <tr>
                                <td>
                                    <strong>{{ $category }}</strong>
                                </td>

                                <td>
                                    {{ $report?->report_number ?? 'No creado' }}
                                </td>

                                <td>
                                    {{ $report?->items_count ?? '—' }}
                                </td>

                                <td>
                                    @if($report)
                                        <a
                                            href="{{ route('admin.controlled-products.show', $report) }}"
                                            class="admin-btn admin-btn--secondary"
                                        >
                                            Abrir / editar
                                        </a>
                                    @else
                                        <form
                                            method="POST"
                                            action="{{ route('admin.controlled-products.store') }}"
                                        >
                                            @csrf

                                            <input type="hidden" name="title" value="Reporte de productos controlados">
                                            <input type="hidden" name="report_month" value="{{ $month }}">
                                            <input type="hidden" name="category" value="{{ $category }}">
                                            <input type="hidden" name="notes" value="">

                                            <button type="submit" class="admin-btn admin-btn--primary">
                                                Crear categoría
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection