<div class="search-results-page section-space">
    <div class="container">
        @if ($showSearchHeader)
            <div class="search-results-header">
                <div class="search-results-header__left">
                    <h1 class="search-results-title">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Resultados de tu búsqueda: <span>{{ $search }}</span>
                    </h1>
                    <p class="search-results-count">{{ $totalProducts }} productos encontrados</p>
                </div>

                <div class="search-results-filter">
                    <label for="filter">Filtrar por:</label>
                    <select id="filter" wire:model.live="filter">
                        <option value="">Orden original</option>
                        <option value="name_asc">Nombre A-Z</option>
                        <option value="name_desc">Nombre Z-A</option>
                    </select>
                </div>
            </div>

            <div class="search-results-divider"></div>
        @else
            <h2 class="section-title">Catálogo</h2>
        @endif

        <div class="search-results-divider"></div>

        <div class="catalog-products-grid">
            @forelse ($products as $product)
                @php
                    $priceBs = $exchangeRate > 0 ? $product->price * $exchangeRate : null;
                @endphp

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

                        <p class="product-card__price">
                            @if($priceBs)
                                Bs. {{ number_format($priceBs, 2, ',', '.') }} |
                            @endif
                            $ {{ number_format($product->price, 2, ',', '.') }}
                        </p>

                        <form action="{{ route('cart.add', $product->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="product-card__button">Agregar al carrito</button>
                        </form>
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