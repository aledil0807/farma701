<aside class="admin-sidebar">
    <div class="admin-sidebar__brand">
        <a href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('assets/img/Logo.png') }}" alt="Farmacia 701">
        </a>
        <a href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('assets/img/logo701.png') }}" alt="Farmacia 701">
        </a>
    </div>

    <nav class="admin-sidebar__nav">
        <a href="{{ route('admin.dashboard') }}"
            class="admin-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Panel de Control</span>
        </a>

        <a href="{{ route('import.form') }}"
            class="admin-sidebar__link {{ request()->routeIs('import.form') ? 'is-active' : '' }}">
            <i class="fa-solid fa-file-excel"></i>
            <span>Importar Excel</span>
        </a>

        <a href="{{ route('admin.products.import-images.show') }}"
            class="admin-sidebar__link {{ request()->routeIs('admin.products.import-images.show') ? 'is-active' : '' }}">
            <i class="fa-solid fa-images"></i>
            <span>Importar Imágenes</span>
        </a>

        <a href="{{ route('admin.monthly-products.index') }}"
            class="admin-sidebar__link {{ request()->routeIs('admin.monthly-products.*') ? 'is-active' : '' }}">
            <i class="fa-solid fa-star"></i>
            <span>Productos del Mes</span>
        </a>

        <a href="{{ route('admin.laboratories.index') }}"
            class="admin-sidebar__link {{ request()->routeIs('admin.laboratories.*') ? 'is-active' : '' }}">
            <i class="fa-solid fa-flask"></i>
            <span>Laboratorios</span>
        </a>

        <a href="{{ route('admin.banners.index') }}"
            class="admin-sidebar__link {{ request()->routeIs('admin.banners.*') ? 'is-active' : '' }}">
            <i class="fa-solid fa-panorama"></i>
            <span>Banners</span>
        </a>

        <a href="{{ route('admin.exchange-rate.edit') }}"
            class="admin-sidebar__link {{ request()->routeIs('admin.exchange-rate.*') ? 'is-active' : '' }}">
            <i class="fa-solid fa-dollar-sign"></i>
            <span>Tasa del Día</span>
        </a>

        <a href="{{ route('admin.quotes.index') }}"
            class="admin-sidebar__link {{ request()->routeIs('admin.quotes.*') ? 'is-active' : '' }}">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Presupuestos</span>
        </a>

        <a href="{{ route('admin.controlled-products.index') }}"
            class="admin-sidebar__link {{ request()->routeIs('admin.controlled-products.*') ? 'is-active' : '' }}">
            <i class="fa-solid fa-notes-medical"></i>
            <span>Productos Controlados</span>
        </a>

        @if(auth()->user()?->can_access_accounting)
            <a href="{{ route('admin.accounting.summary') }}"
                class="admin-sidebar__link {{ request()->routeIs('admin.accounting.summary') || request()->routeIs('admin.accounting.closures.*') ? 'is-active' : '' }}">
                <i class="fa-solid fa-cash-register"></i>
                <span>Cierres de caja</span>
            </a>

            <a href="{{ route('admin.accounting.cashiers.index') }}"
                class="admin-sidebar__link {{ request()->routeIs('admin.accounting.cashiers.*') ? 'is-active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>Cajeros</span>
            </a>
        @endif

        @if(auth()->user()?->can_access_metrics)
            <a href="{{ route('admin.metrics.index') }}"
                class="admin-sidebar__link {{ request()->routeIs('admin.metrics.*') ? 'is-active' : '' }}">
                <i class="fa-solid fa-chart-line"></i>
                <span>Métricas</span>
            </a>
        @endif
    </nav>

    <div class="admin-sidebar__footer">
        <a href="{{ route('home') }}" class="admin-sidebar__link">
            <i class="fa-solid fa-house"></i>
            <span>Ver tienda</span>
        </a>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <button type="submit" class="admin-sidebar__logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>