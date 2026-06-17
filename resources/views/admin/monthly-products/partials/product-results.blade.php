@forelse($products as $product)
    <div class="admin-monthly-item">
        <label class="admin-monthly-item__check">
            <input
                type="checkbox"
                value="{{ $product->id }}"
                data-monthly-product-checkbox
                data-product-id="{{ $product->id }}"
            >
            <span></span>
        </label>

        <div class="admin-monthly-item__image">
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
        </div>

        <div class="admin-monthly-item__info">
            <h3>{{ $product->name }}</h3>

            <p>
                {{ $product->laboratory?->name ?? 'Sin laboratorio' }}
                @if($product->category)
                    · {{ $product->category->name }}
                @endif
            </p>

            <small>
                Stock: {{ $product->cantidad }} · Precio: $ {{ number_format($product->price, 2, '.', ',') }}
            </small>
        </div>

        <div class="admin-monthly-item__order">
            <label>Orden</label>
            <input
                type="number"
                name="monthly_order[{{ $product->id }}]"
                min="1"
                placeholder="-"
            >
        </div>
    </div>
@empty
    <p class="admin-empty-text">
        No se encontraron productos con esa búsqueda.
    </p>
@endforelse