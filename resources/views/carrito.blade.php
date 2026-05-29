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
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/Logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.cartInitial = {
            unitsCount: {{ app(\App\Services\CartService::class)->totals()['units_count'] ?? 0 }}
    };
    </script>
    <script defer src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
                    <!-- <a href="#" aria-label="Usuario"><i class="fa-solid fa-user"></i></a> -->

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
    </header>

    <main class="cart-page">
        <div class="cart-page__grid">
            <section class="cart-summary" x-data="cartPage({
                    items: {{ \Illuminate\Support\Js::from(array_values($cartItems)) }},
                    totals: {{ \Illuminate\Support\Js::from($totals) }},
                    exchangeRate: {{ $exchangeRate }}
                })">
                <div class="cart-summary__header">
                    <h1>Resumen de compra</h1>
                    <p>Tasa del día <span x-text="formatBs(exchangeRate)"></span> Bs</p>
                </div>

                <div class="cart-items">
                    <template x-if="items.length > 0">
                        <div>
                            <template x-for="item in items" :key="item.product_id">
                                <article class="cart-item">
                                    <div class="cart-item__image">
                                        <img :src="item.image_url" :alt="item.name">
                                    </div>

                                    <div class="cart-item__info">
                                        <h3 x-text="item.name"></h3>
                                        <p x-text="item.laboratory"></p>
                                        <template x-if="item.is_controlled">
                                            <p class="cart-item__controlled">Requiere récipe</p>
                                        </template>
                                        <strong>
                                            Bs. <span
                                                x-text="formatBs(Number(item.price_usd || 0) * exchangeRate)"></span>
                                            | $
                                            <span x-text="formatUsd(item.price_usd)"></span>
                                        </strong>
                                    </div>

                                    <div class="cart-item__qty">
                                        <button type="button" class="qty-btn"
                                            @click="decrement(item.product_id)">-</button>

                                        <span class="qty-value" x-text="item.quantity"></span>

                                        <button type="button" class="qty-btn"
                                            @click="increment(item.product_id)">+</button>
                                    </div>

                                    <div class="cart-item__total">
                                        <span>Total</span>
                                        <strong>
                                            Bs. <span x-text="formatBs(lineBs(item))"></span>
                                            |
                                            $ <span x-text="formatUsd(lineUsd(item))"></span>
                                        </strong>
                                    </div>

                                    <div class="cart-item__remove">
                                        <button type="button" class="remove-btn" @click="remove(item.product_id)"
                                            aria-label="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </article>
                            </template>
                        </div>
                    </template>

                    <template x-if="items.length === 0">
                        <div class="cart-empty">
                            <p>Tu carrito está vacío.</p>
                            <a href="{{ route('home') }}" class="btn-view-more">Ir al catálogo</a>
                        </div>
                    </template>
                </div>

                <div class="cart-summary__footer" x-show="items.length > 0">
                    <button type="button" class="clear-cart-btn" @click="clear()">
                        Vaciar carrito
                        <i class="fa-solid fa-trash"></i>
                    </button>

                    <div class="cart-grand-total">
                        <h2>Total</h2>

                        <p class="cart-grand-total__amount">
                            Bs. <span x-text="formatBs(totals.subtotal_bs)"></span>
                            |
                            $ <span x-text="formatUsd(totals.subtotal_usd)"></span>
                        </p>

                        <p class="cart-grand-total__discount">
                            -<span x-text="totals.discount_percent || 0"></span>% descuento:
                            Bs. <span x-text="formatBs(totals.discount_bs)"></span>
                            |
                            $ <span x-text="formatUsd(totals.discount_usd)"></span>
                        </p>
                    
                        <p class="cart-grand-total__subtotal">
                            Total a cancelar:
                            Bs. <span x-text="formatBs(totals.total_bs)"></span>
                            |
                            $ <span x-text="formatUsd(totals.total_usd)"></span>
                        </p>
                    </div>
                </div>
            </section>

            <aside class="cart-client">
                <h2>Datos del cliente</h2>
                @if ($errors->any())
                    <div class="cart-alert cart-alert--error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
                <form action="{{ route('cart.checkout') }}" method="POST" class="cart-client__form"
                    x-data="{ deliveryType: '{{ old('delivery_type', '') }}' }">
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
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                        </div>

                        <div>
                            <label for="delivery_type">Tipo de entrega</label>
                            <select id="delivery_type" name="delivery_type" x-model="deliveryType">
                                <option value="">Seleccionar</option>
                                <option value="pickup" {{ old('delivery_type') === 'pickup' ? 'selected' : '' }}>Retiro en
                                    tienda</option>
                                <option value="delivery" {{ old('delivery_type') === 'delivery' ? 'selected' : '' }}>
                                    Delivery</option>
                            </select>
                        </div>
                    </div>

                    <div class="cart-field" x-show="deliveryType === 'delivery'" x-transition>
                        <label for="delivery_address">Dirección de envío</label>
                        <textarea id="delivery_address" name="delivery_address" class="cart-field__textarea" rows="3"
                            placeholder="Escribe la dirección completa de entrega">{{ old('delivery_address') }}</textarea>
                    </div>

                    <div class="cart-field">
                        <label for="payment_method">Método de pago</label>
                        <select id="payment_method" name="payment_method">
                            <option value="">Seleccionar</option>
                            <option value="pago_movil" {{ old('payment_method') === 'pago_movil' ? 'selected' : '' }}>Pago
                                móvil</option>
                            <option value="transferencia" {{ old('payment_method') === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                            <option value="efectivo_usd" {{ old('payment_method') === 'efectivo_usd' ? 'selected' : '' }}>
                                Efectivo USD</option>
                            <option value="efectivo_bs" {{ old('payment_method') === 'efectivo_bs' ? 'selected' : '' }}>
                                Efectivo Bs</option>
                            <option value="tarjeta" {{ old('payment_method') === 'tarjeta' ? 'selected' : '' }}>Tarjeta de
                                crédito/débito</option>
                        </select>
                    </div>
                    <div class="cart-field">
                        <label for="attention_code">Código Atención</label>
                        <input type="text" id="attention_code" name="attention_code" value="{{ old('attention_code') }}"
                            placeholder="Opcional">
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
            <a href="{{ route('admin.login') }}" aria-label="Carrito" class="cart-link">
                <p>Farmacia 701, C.A</p>
            </a>
        </div>
    </footer>
</body>

</html>