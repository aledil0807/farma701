<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito | Farmacia 701</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <header class="site-header">
        <div class="topbar">
            <div class="container topbar__content">
                <p>Farmacia 701 ¡Somos tus aliados en salud!</p>
            </div>
        </div>

        <div class="header-main">
            <div class="container header-main__content">
                <a href="{{ route('home') }}" class="brand">
                    <img src="{{ asset('assets/img/logo-nuevo.jpeg') }}" alt="Farmacia 701">
                </a>

                <form class="search-bar" action="{{ route('search.results') }}" method="get">
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="Buscar productos, marcas o categorías">
                    <button type="submit" aria-label="Buscar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>

                <div class="header-actions">
                    <a href="#" aria-label="Usuario"><i class="fa-solid fa-user"></i></a>
                    <a href="{{ route('cart.index') }}" aria-label="Carrito" class="cart-link">
                        <i class="fa-solid fa-cart-shopping"></i>
                        @if(($totals['units_count'] ?? 0) > 0)
                            <span class="cart-badge">{{ $totals['units_count'] }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="cart-page">
        <div class="cart-page__grid">
            <section class="cart-summary">
                @if(session('stock_errors'))
                    <div class="cart-alert cart-alert--error">
                        <ul>
                            @foreach(session('stock_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('success'))
                    <div class="cart-alert cart-alert--success">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="cart-summary__header">
                    <h1>Resumen de compra</h1>
                    <p>Tasa del día <span>{{ number_format($exchangeRate, 2, ',', '.') }} Bs</span></p>
                </div>

                @if(count($cartItems))
                    <div class="cart-items">
                        @foreach($cartItems as $item)
                            @php
                                $lineUsd = $item['price_usd'] * $item['quantity'];
                                $lineBs = $exchangeRate > 0 ? $lineUsd * $exchangeRate : 0;
                            @endphp

                            <article class="cart-item">
                                <div class="cart-item__image">
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}">
                                </div>

                                <div class="cart-item__info">
                                    <h3>{{ $item['name'] }}</h3>
                                    <p>{{ $item['laboratory'] }}</p>
                                    <strong>
                                        Bs. {{ number_format($lineBs, 2, ',', '.') }}
                                        | $
                                        {{ number_format($lineUsd, 2, '.', ',') }}
                                    </strong>
                                </div>

                                <div class="cart-item__qty">
                                    <form action="{{ route('cart.decrement', $item['product_id']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="qty-btn">-</button>
                                    </form>

                                    <span class="qty-value">{{ $item['quantity'] }}</span>

                                    <form action="{{ route('cart.increment', $item['product_id']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="qty-btn">+</button>
                                    </form>
                                </div>

                                <div class="cart-item__total">
                                    <span>Total</span>
                                    <strong>
                                        Bs. {{ number_format($lineBs, 2, ',', '.') }}
                                        | $
                                        {{ number_format($lineUsd, 2, '.', ',') }}
                                    </strong>
                                </div>

                                <div class="cart-item__remove">
                                    <form action="{{ route('cart.remove', $item['product_id']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="remove-btn" aria-label="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="cart-summary__footer">
                        <form action="{{ route('cart.clear') }}" method="POST">
                            @csrf
                            <button type="submit" class="clear-cart-btn">
                                Vaciar carrito
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>

                        <div class="cart-grand-total">
                            <h2>Total a cancelar</h2>
                            <p class="cart-grand-total__discount">-5% descuento: Bs. 1.234,56 | $ 1.23</p>
                            <p class="cart-grand-total__amount">
                                Bs. {{ number_format($totals['subtotal_bs'], 2, ',', '.') }}
                                | $
                                {{ number_format($totals['subtotal_usd'], 2, '.', ',') }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="cart-empty">
                        <p>Tu carrito está vacío.</p>
                        <a href="{{ route('home') }}" class="btn-view-more">Ir al catálogo</a>
                    </div>
                @endif
            </section>

            <aside class="cart-client">
                <h2>Datos del cliente</h2>

                <form action="{{ route('cart.checkout') }}" method="POST" class="cart-client__form">
                    @csrf

                    <div class="cart-field">
                        <label for="document">Cédula o RIF</label>
                        <input type="text" id="document" name="document">
                    </div>

                    <div class="cart-field">
                        <label for="name">Nombre y apellido ó empresa</label>
                        <input type="text" id="name" name="name">
                    </div>

                    <div class="cart-field cart-field--row">
                        <div>
                            <label for="phone">Número de teléfono</label>
                            <input type="text" id="phone" name="phone">
                        </div>

                        <div>
                            <label for="delivery_type">Tipo de entrega</label>
                            <select id="delivery_type" name="delivery_type">
                                <option value="">Seleccionar</option>
                                <option value="pickup">Retiro en tienda</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>
                    </div>

                    <div class="cart-field">
                        <label for="payment_method">Método de pago</label>
                        <select id="payment_method" name="payment_method">
                            <option value="">Seleccionar</option>
                            <option value="pago_movil">Pago móvil</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="efectivo_usd">Efectivo USD</option>
                            <option value="efectivo_bs">Efectivo Bs</option>
                        </select>
                    </div>

                    <button type="submit" class="checkout-btn">Procesar compra</button>
                </form>
            </aside>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container search-footer__content">
            <p>Encuentra todos tus medicamentos, productos de salud, cuidado personal y suplementos deportivos.</p>
            <p>Av. 17 de diciembre C/C Calle Madrid, Local # 28, Séctor Negro Primero, Parroquia catedral, Frente a la
                clínica Santa Ana, Ciudad Bolívar - Venezuela.</p>
            <p>¡Somos tus Aliados en Salud!</p>
            <p>Farmacia 701, C.A</p>
        </div>
    </footer>

    <script src="{{ asset('js/main.js') }}"></script>
</body>

</html>