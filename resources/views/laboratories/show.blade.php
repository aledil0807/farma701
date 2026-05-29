<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Farmacia 701 | Laboratorio: {{ $laboratory->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/Logo.png') }}">
    <script>
        window.cartInitial = {
            unitsCount: {{ app(\App\Services\CartService::class)->totals()['units_count'] ?? 0 }}
    };
    </script>
    <script defer src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}" ></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @livewireStyles
</head>

<body>
    <div x-data="stockToast()" x-show="visible" x-transition class="stock-toast" x-text="message"></div>
    <header class="site-header">
        <div class="topbar">
            <div class="container topbar__content">
                <p>Farmacia 701 ¡Somos tus aliados en salud!</p>
            </div>
        </div>

        <div class="header-main">
            <div class="container header-main__content">
                <a href="{{ route('home') }}" class="brand">
                    <img src="{{ asset('assets/img/Logo.png') }}" alt="Farmacia 701">
                </a>

                <form class="search-bar" action="{{ route('search.results') }}" method="get">
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="Buscar productos, marcas o categorías">
                    <button type="submit" aria-label="Buscar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>

                <div class="header-actions">
                    

                    <div x-data>
                        <a href="{{ route('cart.index') }}" aria-label="Carrito" class="cart-link">
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
                <ul class="nav-menu">
                    <li><a href="{{ route('home') }}">Inicio</a></li>

                    <!--<li class="has-dropdown">
                        <button class="dropdown-toggle" type="button">
                            Laboratorios <i class="fa-solid fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#">Calox</a></li>
                            <li><a href="#">Pharmetique</a></li>
                            <li><a href="#">Rowe</a></li>
                            <li><a href="#">Valmorca</a></li>
                            <li><a href="#">Farma</a></li>
                        </ul>
                    </li>

                    <li class="has-dropdown">
                        <button class="dropdown-toggle" type="button">
                            Categorías <i class="fa-solid fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#">Salud y Medicamentos</a></li>
                            <li><a href="#">Cuidado Personal</a></li>
                            <li><a href="#">Nutrición</a></li>
                            <li><a href="#">Suministros Médicos</a></li>
                            <li><a href="#">Hogar</a></li>
                            <li><a href="#">Equipos Médicos</a></li>
                            <li><a href="#">Línea Infantil</a></li>
                            <li><a href="#">Alimentos y Bebidas</a></li>
                        </ul>
                    </li>

                    <li class="has-dropdown">
                        <button class="dropdown-toggle" type="button">
                            Directorio Médico <i class="fa-solid fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#">Medicina General</a></li>
                            <li><a href="#">Pediatría</a></li>
                            <li><a href="#">Cardiología</a></li>
                            <li><a href="#">Ginecología</a></li>
                            <li><a href="#">Traumatología</a></li>
                        </ul>
                    </li>-->
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="lab-hero">
            <div class="container">
                <div class="lab-hero__banner">
                    <div class="lab-hero__logo lab-hero__logo--lab">
                        <img src="{{ $laboratory->logo_url }}" alt="{{ $laboratory->name }}">
                    </div>

                    <div class="lab-hero__divider">
                        <span class="lab-hero__line"></span>
                        <span class="lab-hero__dot"></span>
                        <span class="lab-hero__line"></span>
                    </div>

                    <div class="lab-hero__logo lab-hero__logo--pharmacy">
                        <img src="{{ asset('assets/img/Logo.png') }}" alt="Farmacia 701">
                    </div>
                </div>
            </div>
    </section>

        @livewire('product-search', [
            'initialSearch' => '',
            'laboratoryId' => $laboratory->id,
            'showSearchHeader' => false,
            'customTitle' => 'PRODUCTOS DISPONIBLES'
        ])
    </main>

    <footer class="site-footer">
        <div class="container search-footer__content">
            <p>Encuentra todos tus medicamentos, productos de salud, cuidado personal y suplementos deportivos.</p>
            <p>Av. 17 de diciembre C/C Calle Madrid, Local # 28, Séctor Negro Primero, Parroquia catedral, Frente a la
                clínica Santa Ana, Ciudad Bolívar - Venezuela.</p>
            <p>¡Somos tus Aliados en Salud!</p>
            <a href="{{ route('admin.login') }}" aria-label="Carrito" class="cart-link"><p>Farmacia 701, C.A</p></a>
            
        </div>
    </footer>

    @livewireScripts
</body>

</html>