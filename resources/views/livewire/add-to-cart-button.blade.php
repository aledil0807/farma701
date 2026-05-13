<div class="cart-inline-control">
    @if ($quantity <= 0)
        <button type="button" class="product-card__button" wire:click="add">
            Agregar al carrito
        </button>
    @else
        <div class="product-qty-control">
            <button type="button" class="product-qty-control__btn" wire:click="decrement">-</button>
            <span class="product-qty-control__value">{{ $quantity }}</span>
            <button type="button" class="product-qty-control__btn" wire:click="increment">+</button>
        </div>
    @endif
</div>