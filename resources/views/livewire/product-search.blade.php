<div class="catalog-section section-space">
    <div class="container">
        <h2 class="section-title">Catálogo</h2>

        <div class="catalog-products-grid">
            @forelse ($products as $product)
                <a class="product-card" href="#">
                    <div class="product-card__image">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                    </div>

                    <div class="product-card__body">
                        <div class="product-card__title-wrap">
                            <h3 class="product-card__title">{{ $product->name }}</h3>
                        </div>

                        <p class="product-card__brand">
                            {{ $product->laboratory?->name ?? 'Sin laboratorio' }}
                        </p>

                        <span class="product-card__status {{ $product->cantidad > 0 ? 'is-available' : 'is-unavailable' }}">
                            {{ $product->cantidad > 0 ? '¡Disponible!' : 'No disponible' }}
                        </span>

                        @php
                            $priceBs = $exchangeRate > 0 ? $product->price * $exchangeRate : null;
                        @endphp
                        <p class="product-card__price">
                            $ {{ number_format($product->price, 2, ',', '.') }} @if($priceBs)
                               | Bs. {{ number_format($priceBs, 2, ',', '.') }}
                            @endif
                        </p>

                        <span class="product-card__button">Agregar al carrito</span>
                    </div>
                </a>

            @empty
                <p class="catalog-empty">No se encontraron productos.</p>
            @endforelse
        </div>

        @if ($hasMoreProducts)
            <div class="catalog-footer" style="text-align:center; margin-top:20px;">
                <button type="button" class="btn-view-more" wire:click="loadMore">
                    <span wire:loading.remove wire:target="loadMore">Ver más productos</span>
                    <span wire:loading wire:target="loadMore">Cargando...</span>
                </button>
            </div>
        @endif
    </div>
</div>