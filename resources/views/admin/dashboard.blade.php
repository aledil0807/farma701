@extends('layouts.admin')

@section('content')
<div class="admin-dashboard">
    <div class="admin-dashboard__header">
        <div>
            <h1>Panel de Control</h1>
        </div>
    </div>

    <section class="admin-dashboard-stats">
        <article class="admin-dashboard-stat-card">
            <div>
                <span>Tasa del día</span>
                <strong>
                    @if($stats['exchange_rate'])
                        Bs. {{ number_format((float) $stats['exchange_rate'], 2, ',', '.') }}
                    @else
                        No disponible
                    @endif
                </strong>
            </div>

            <span class="admin-dashboard-stat-card__icon is-green">
                <i class="fa-solid fa-dollar-sign"></i>
            </span>
        </article>

        <article class="admin-dashboard-stat-card">
            <div>
                <span>Productos activos</span>
                <strong>{{ $stats['active_products'] }}</strong>
            </div>

            <span class="admin-dashboard-stat-card__icon is-blue">
                <i class="fa-solid fa-boxes-stacked"></i>
            </span>
        </article>

        <article class="admin-dashboard-stat-card">
            <div>
                <span>Laboratorios visibles</span>
                <strong>{{ $stats['visible_labs'] }}</strong>
            </div>

            <span class="admin-dashboard-stat-card__icon is-purple">
                <i class="fa-solid fa-flask"></i>
            </span>
        </article>

        <article class="admin-dashboard-stat-card">
            <div>
                <span>Productos del mes</span>
                <strong>{{ $stats['monthly_products'] }}</strong>
            </div>

            <span class="admin-dashboard-stat-card__icon is-orange">
                <i class="fa-solid fa-star"></i>
            </span>
        </article>
    </section>

    <!-- <section class="admin-dashboard-grid">
        <article class="admin-dashboard-card admin-dashboard-card--wide">
            <div class="admin-dashboard-card__head">
                <div>
                    <h2>Accesos rápidos</h2>
                    <p>Gestiona las áreas principales del sistema.</p>
                </div>
            </div>

            <div class="admin-dashboard-shortcuts">
                <a href="{{ route('import.form') }}" class="admin-dashboard-shortcut">
                    <span><i class="fa-solid fa-file-excel"></i></span>
                    <strong>Importar Excel</strong>
                    <small>Actualizar productos y stock</small>
                </a>

                <a href="{{ route('admin.products.import-images.show') }}" class="admin-dashboard-shortcut">
                    <span><i class="fa-solid fa-images"></i></span>
                    <strong>Importar imágenes</strong>
                    <small>Cargar imágenes de productos</small>
                </a>

                <a href="{{ route('admin.monthly-products.index') }}" class="admin-dashboard-shortcut">
                    <span><i class="fa-solid fa-star"></i></span>
                    <strong>Productos del mes</strong>
                    <small>Elegir productos destacados</small>
                </a>

                <a href="{{ route('admin.quotes.index') }}" class="admin-dashboard-shortcut">
                    <span><i class="fa-solid fa-file-invoice-dollar"></i></span>
                    <strong>Presupuestos</strong>
                    <small>Armar presupuestos para clientes</small>
                </a>

                <a href="{{ route('admin.laboratories.index') }}" class="admin-dashboard-shortcut">
                    <span><i class="fa-solid fa-flask"></i></span>
                    <strong>Laboratorios</strong>
                    <small>Editar logos y nombres</small>
                </a>

                <a href="{{ route('admin.controlled-products.index') }}" class="admin-dashboard-shortcut">
                    <span><i class="fa-solid fa-notes-medical"></i></span>
                    <strong>Controlados</strong>
                    <small>Reportes mensuales</small>
                </a>
            </div>
        </article>

        <article class="admin-dashboard-card">
            <div class="admin-dashboard-card__head">
                <div>
                    <h2>Estado del sistema</h2>
                    <p>Resumen de módulos activos.</p>
                </div>
            </div>

            <div class="admin-dashboard-system-list">
                <div>
                    <span>Banners activos</span>
                    <strong>{{ $stats['active_banners'] }}</strong>
                </div>

                <div>
                    <span>Presupuestos creados</span>
                    <strong>{{ $stats['quotes_count'] }}</strong>
                </div>

                <div>
                    <span>Reportes controlados</span>
                    <strong>{{ $stats['controlled_reports_count'] }}</strong>
                </div>
            </div>
        </article>

        <article class="admin-dashboard-card">
            <div class="admin-dashboard-card__head">
                <div>
                    <h2>Últimos presupuestos</h2>
                    <p>Presupuestos creados recientemente.</p>
                </div>

                <a href="{{ route('admin.quotes.index') }}">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            </div>

            <div class="admin-dashboard-list">
                @forelse($latestQuotes as $quote)
                    <a href="{{ route('admin.quotes.edit', $quote) }}" class="admin-dashboard-list-item">
                        <div>
                            <strong>{{ $quote->quote_number ?? 'Presupuesto' }}</strong>
                            <span>{{ $quote->employee_name ?? 'Usuario no indicado' }}</span>
                        </div>

                        <small>
                            $ {{ number_format((float) $quote->total_usd, 2, '.', ',') }}
                        </small>
                    </a>
                @empty
                    <p class="admin-dashboard-empty">Todavía no hay presupuestos.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-dashboard-card">
            <div class="admin-dashboard-card__head">
                <div>
                    <h2>Productos con poco stock</h2>
                    <p>Productos activos con 4 unidades o menos.</p>
                </div>
            </div>

            <div class="admin-dashboard-list">
                @forelse($lowStockProducts as $product)
                    <div class="admin-dashboard-list-item">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $product->laboratory?->name ?? 'Sin laboratorio' }}</span>
                        </div>

                        <small>{{ $product->cantidad }} und.</small>
                    </div>
                @empty
                    <p class="admin-dashboard-empty">No hay productos con poco stock.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-dashboard-card">
            <div class="admin-dashboard-card__head">
                <div>
                    <h2>Últimos reportes controlados</h2>
                    <p>Reportes mensuales recientes.</p>
                </div>

                <a href="{{ route('admin.controlled-products.index') }}">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            </div>

            <div class="admin-dashboard-list">
                @forelse($latestControlledReports as $report)
                    <a href="{{ route('admin.controlled-products.show', $report) }}" class="admin-dashboard-list-item">
                        <div>
                            <strong>{{ $report->report_number ?? 'Reporte' }}</strong>
                            <span>
                                {{ $report->category ?: 'Sin categoría' }}
                                ·
                                {{ $report->report_month ?: 'Sin mes' }}
                            </span>
                        </div>

                        <small>{{ $report->items_count }} prod.</small>
                    </a>
                @empty
                    <p class="admin-dashboard-empty">Todavía no hay reportes.</p>
                @endforelse
            </div>
        </article>
    </section> -->
</div>
@endsection