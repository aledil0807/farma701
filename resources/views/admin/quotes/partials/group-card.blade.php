<div class="admin-card quote-group-card" data-quote-group data-group-id="{{ $group->id }}"
    x-data="{ imageModeOpen: false, captureMode: false }" @keydown.escape.window="imageModeOpen = false">
    <div class="quote-group-card__header">
        <div>

            <h2>{{ $group->name }}</h2>
            <button type="button" class="admin-btn admin-btn--secondary quote-image-mode-btn" @click="
    captureMode = true;
    imageModeOpen = true;

    setTimeout(async () => {
        await window.downloadQuoteCapture($el.closest('[data-quote-group]'));
        imageModeOpen = false;
        captureMode = false;
    }, 350);
">
                Descargar Imagen
            </button>
            <p>
                Subtotal:
                $ {{ number_format((float) $group->subtotal_usd, 2, '.', ',') }}
                |
                Bs. {{ number_format((float) $group->subtotal_bs, 2, ',', '.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('admin.quotes.groups.destroy', $group) }}"
            onsubmit="return confirm('¿Eliminar este conjunto?')">
            @csrf
            @method('DELETE')

            <button type="submit" class="admin-btn admin-btn--danger">
                Eliminar conjunto
            </button>
        </form>
        <div class="quote-capture-overlay" x-show="imageModeOpen" x-transition.opacity x-cloak>
            <div class="quote-capture-backdrop" @click="imageModeOpen = false"></div>

            <div class="quote-capture-modal" @click.stop>
                <div class="quote-capture-actions">
                    <button type="button" class="quote-capture-close" @click="imageModeOpen = false">
                        Cerrar
                    </button>
                </div>

                <div class="quote-capture-card" data-quote-capture-content>
                    <div class="quote-capture-card__header">
                        <div>
                            <span class="quote-capture-card__eyebrow">Presupuesto</span>
                            <h2>{{ $group->name }}</h2>
                        </div>

                        <div class="quote-capture-card__date">
                            {{ now()->format('d/m/Y') }}
                        </div>
                    </div>

                    @if($group->items->isEmpty())
                        <div class="quote-capture-empty">
                            Este conjunto todavía no tiene productos.
                        </div>
                    @else
                        <div class="quote-capture-items">
                            @foreach($group->items as $item)
                                @php
                                    $productImage = $item->product?->image_url ?? asset('assets/img/product-placeholder.png');
                                @endphp

                                <div class="quote-capture-item">
                                    <div class="quote-capture-item__image">
                                        <img src="{{ $productImage }}" alt="{{ $item->product_name }}">
                                    </div>

                                    <div class="quote-capture-item__main">
                                        <h3>{{ $item->product_name }}</h3>

                                        @if($item->laboratory_name)
                                            <p>{{ $item->laboratory_name }}</p>
                                        @endif
                                    </div>

                                    <div class="quote-capture-item__qty">
                                        x{{ $item->quantity }}
                                    </div>

                                    <div class="quote-capture-item__price">
                                        <span>
                                            $ {{ number_format((float) $item->unit_price_usd, 2, '.', ',') }}
                                        </span>

                                        <strong>
                                            $ {{ number_format((float) $item->subtotal_usd, 2, '.', ',') }}
                                        </strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="quote-capture-total">
                            <span>Total del conjunto</span>

                            <div>
                                <strong>
                                    $ {{ number_format((float) $group->subtotal_usd, 2, '.', ',') }}
                                </strong>

                                <small>
                                    Bs. {{ number_format((float) $group->subtotal_bs, 2, ',', '.') }}
                                </small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.quotes.groups.update', $group) }}">
        @csrf
        @method('PUT')

        <div class="admin-form-grid">
            <div class="admin-form__group">
                <label class="admin-form__label">Nombre</label>
                <input type="text" name="name" class="admin-form__input-text" value="{{ $group->name }}" required>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Orden</label>
                <input type="number" name="position" class="admin-form__input-text" value="{{ $group->position }}"
                    min="1" required>
            </div>

            <div class="admin-form__group admin-form__group--button">
                <button type="submit" class="admin-btn admin-btn--secondary">
                    Actualizar conjunto
                </button>
            </div>
        </div>
    </form>

    <hr class="admin-divider">

    <h3>Agregar producto</h3>

    <div class="admin-form-grid">
        <div class="admin-form__group">
            <label class="admin-form__label">Buscar producto</label>
            <input type="text" class="admin-form__input-text"
                placeholder="Buscar por nombre, laboratorio o categoría..." data-quote-search-input>
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label">Cantidad</label>
            <input type="number" min="1" value="1" class="admin-form__input-text" data-quote-product-qty>
        </div>
    </div>

    <div class="quote-product-results" data-quote-search-results></div>
    <button type="button" class="admin-btn admin-btn--secondary quote-products-load-more" data-quote-load-more-products
        style="display: none;">
        Cargar más productos
    </button>

    <form method="POST" action="{{ route('admin.quotes.items.store', $group) }}" data-add-product-form
        data-quote-ajax-form>
        @csrf
        <input type="hidden" name="product_id" data-add-product-id>
        <input type="hidden" name="quantity" data-add-product-quantity value="1">
    </form>

    <hr class="admin-divider">

    <h3>Productos del conjunto</h3>

    @if($group->items->isEmpty())
        <p>Este conjunto todavía no tiene productos.</p>
    @else
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Laboratorio</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal $</th>
                    <th>Subtotal Bs.</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach($group->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->laboratory_name ?? '-' }}</td>

                        <td>
                            <form method="POST" action="{{ route('admin.quotes.items.update', $item) }}" data-quote-ajax-form>
                                @csrf
                                @method('PUT')

                                <div class="quote-item-edit quote-item-edit--auto">
                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1"
                                        class="admin-form__input-text" data-quote-item-auto-update title="Cantidad">
                                </div>
                            </form>
                        </td>
                        <td>{{ $item->unit_price_usd}}</td>

                        <td>$ {{ number_format((float) $item->subtotal_usd, 2, '.', ',') }}</td>
                        <td>Bs. {{ number_format((float) $item->subtotal_bs, 2, ',', '.') }}</td>

                        <td>
                            <form method="POST" action="{{ route('admin.quotes.items.destroy', $item) }}" data-quote-ajax-form
                                data-confirm-message="¿Eliminar este producto?">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="admin-btn admin-btn--danger">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>