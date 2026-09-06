<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Farmacia 701 | Inicio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ $assetVersion }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/Logo.png') }}">
    <script>
        window.cartInitial = {
            unitsCount: {{ app(\App\Services\CartService::class)->totals()['units_count'] ?? 0 }}
    };
    </script>
    <script defer src="{{ asset('js/main.js') }}?v={{ $assetVersion }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @livewireStyles
</head>

<body>
    <div x-cloak style="display: none;" x-data="stockToast()" x-show.important="visible" x-transition
        class="stock-toast" x-text="message"></div>
    <x-header />
    <x-floating-cart />

    <main>
        <livewire:product-search :initial-search="request('q')" :show-search-header="true" />
    </main>

    <footer class="site-footer">
        <div class="container search-footer__content">
            <p>Encuentra todos tus medicamentos, productos de salud, cuidado personal y suplementos deportivos.</p>
            <p>Av. 17 de diciembre C/C Calle Madrid, Local # 28, Séctor Negro Primero, Parroquia catedral, Frente a la
                clínica Santa Ana, Ciudad Bolívar - Venezuela.</p>
            <p>¡Somos tus Aliados en Salud!</p>
            <a href="{{ route('admin.login') }}" aria-label="Carrito" class="cart-link">
                <p>Farmacia 701, C.A</p>
            </a>

        </div>
    </footer>

    @livewireScripts
</body>

</html>