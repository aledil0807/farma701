<header class="site-header" x-data="{ mobileMenuOpen: false, mobileLabsOpen: false, labsQuery: '' }">
    <div class="topbar">
        <div class="container topbar__content">
            <img src="{{ asset('assets/img/cashea-seeklogo.png') }}" alt="Farmacia 701">
            <p class="topbar__text">
                <span>Compra ahora, paga después,</span>
                <span>en 1 cuota sin intereses</span>
            </p>
        </div>
    </div>

    <div class="header-main">
        <div class="header-main__content">
            <button class="mobile-menu-toggle" type="button" aria-label="Abrir menú"
                @click="mobileMenuOpen = !mobileMenuOpen; if (!mobileMenuOpen) { mobileLabsOpen = false; labsQuery = '' }"
                :aria-expanded="mobileMenuOpen.toString()" :class="{ 'is-open': mobileMenuOpen }">
                <span class="mobile-menu-toggle__bars"></span>
            </button>

            <div class="logo-container">
                <a href="{{ route('home') }}" class="brand">
                    <img src="{{ asset('assets/img/Logo.png') }}" alt="Farmacia 701">
                </a>
                <a href="{{ route('home') }}" class="brand">
                    <img src="{{ asset('assets/img/logo701.png') }}" alt="Farmacia 701">
                </a>
            </div>


            <form class="search-bar" action="{{ route('search.results') }}" method="get">
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Buscar productos, marcas o categorías">
                <button type="submit" aria-label="Buscar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <div class="header-actions">


                <div x-data>
                    <a href="{{ route('cart.index') }}" aria-label="Carrito" class="cart-link header-action-link">
                        <i class="fa-solid fa-cart-shopping"></i>

                        <template x-if="$store.cart.unitsCount > 0">
                            <span class="cart-badge" x-text="$store.cart.unitsCount"></span>
                        </template>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <nav class="main-nav">
        <div class="container">
            <ul class="nav-menu" id="mainNavMenu">
                <li><a href="{{ route('home') }}">Inicio</a></li>

                <li class="has-dropdown labs-dropdown" x-data="labsDropdown()">
                    <button class="dropdown-toggle" type="button" @click="toggle()" :aria-expanded="open.toString()">
                        Laboratorios <i class="fa-solid fa-angle-down"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu--labs" x-cloak x-show="open" @click.outside="close()"
                        x-transition>
                        <div class="dropdown-menu__search">
                            <input type="text" class="dropdown-menu__search-input" placeholder="Buscar laboratorio..."
                                x-model="query">
                        </div>

                        <ul class="dropdown-menu__list">
                            @foreach(($laboratoriesNav ?? collect()) as $lab)
                                <li x-show="matches(@js($lab->name))">
                                    <a href="{{ route('laboratories.show', $lab->id) }}">
                                        {{ $lab->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>

                <li class="nav-hover-line" id="navHoverLine" aria-hidden="true"></li>
            </ul>
        </div>
    </nav>

    <div class="mobile-nav-panel" x-cloak x-show="mobileMenuOpen" x-transition
        @click.outside="mobileMenuOpen = false; mobileLabsOpen = false; labsQuery = '' ">
        <div class="container">
            <ul class="mobile-nav-list">
                <li>
                    <a href="{{ route('home') }}" @click="mobileMenuOpen = false">Inicio</a>
                </li>

                <li class="mobile-nav-section">
                    <button type="button" class="mobile-nav-section__toggle" @click="mobileLabsOpen = !mobileLabsOpen"
                        :aria-expanded="mobileLabsOpen.toString()">
                        <span>Laboratorios</span>
                        <i class="fa-solid" :class="mobileLabsOpen ? 'fa-angle-up' : 'fa-angle-down'"></i>
                    </button>

                    <div x-show="mobileLabsOpen" x-cloak x-transition>
                        <div class="mobile-nav-search">
                            <input type="text" placeholder="Buscar laboratorio..." x-model="labsQuery">
                        </div>

                        <div class="mobile-nav-labs">
                            @foreach(($laboratoriesNav ?? collect()) as $lab)
                                <a href="{{ route('laboratories.show', $lab->id) }}"
                                    x-show="@js(mb_strtolower($lab->name)).includes(labsQuery.toLowerCase())"
                                    @click="mobileMenuOpen = false; mobileLabsOpen = false; labsQuery = ''">
                                    {{ $lab->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>