<a href="{{ route('cart.index') }}" aria-label="Carrito" class="cart-link">
    <i class="fa-solid fa-cart-shopping"></i>

    @if($unitsCount > 0)
        <span class="cart-badge">{{ $unitsCount }}</span>
    @endif
</a>