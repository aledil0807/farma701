<section class="cart-summary">
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
                        <button type="button" class="qty-btn" wire:click="decrement({{ $item['product_id'] }})">-</button>

                        <span class="qty-value">{{ $item['quantity'] }}</span>

                        <button type="button" class="qty-btn" wire:click="increment({{ $item['product_id'] }})">+</button>
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
                        <button type="button" class="remove-btn" wire:click="remove({{ $item['product_id'] }})" aria-label="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="cart-summary__footer">
            <button type="button" class="clear-cart-btn" wire:click="clear">
                Vaciar carrito
                <i class="fa-solid fa-trash"></i>
            </button>

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