<div class="admin-card quote-group-card" data-quote-group data-group-id="{{ $group->id }}">
    <div class="quote-group-card__header">
        <div>
            <h2>{{ $group->name }}</h2>
            <p>
                Subtotal:
                $ {{ number_format((float) $group->subtotal_usd, 2, '.', ',') }}
                |
                Bs. {{ number_format((float) $group->subtotal_bs, 2, ',', '.') }}
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('admin.quotes.groups.destroy', $group) }}"
            onsubmit="return confirm('¿Eliminar este conjunto?')"
        >
            @csrf
            @method('DELETE')

            <button type="submit" class="admin-btn admin-btn--danger">
                Eliminar conjunto
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.quotes.groups.update', $group) }}">
        @csrf
        @method('PUT')

        <div class="admin-form-grid">
            <div class="admin-form__group">
                <label class="admin-form__label">Nombre</label>
                <input
                    type="text"
                    name="name"
                    class="admin-form__input-text"
                    value="{{ $group->name }}"
                    required
                >
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Orden</label>
                <input
                    type="number"
                    name="position"
                    class="admin-form__input-text"
                    value="{{ $group->position }}"
                    min="1"
                    required
                >
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
            <input
                type="text"
                class="admin-form__input-text"
                placeholder="Buscar por nombre, laboratorio o categoría..."
                data-quote-search-input
            >
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label">Cantidad</label>
            <input
                type="number"
                min="1"
                value="1"
                class="admin-form__input-text"
                data-quote-product-qty
            >
        </div>
    </div>

    <div class="quote-product-results" data-quote-search-results></div>

    <form
        method="POST"
        action="{{ route('admin.quotes.items.store', $group) }}"
        data-add-product-form
        data-quote-ajax-form
    >
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
                    <th>Cantidad / Precio</th>
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
                            <form
                                method="POST"
                                action="{{ route('admin.quotes.items.update', $item) }}"
                                data-quote-ajax-form
                            >
                                @csrf
                                @method('PUT')

                                <div class="quote-item-edit">
                                    <input
                                        type="number"
                                        name="quantity"
                                        value="{{ $item->quantity }}"
                                        min="1"
                                        class="admin-form__input-text"
                                    >

                                    <input
                                        type="number"
                                        name="unit_price_usd"
                                        value="{{ $item->unit_price_usd }}"
                                        step="0.01"
                                        min="0"
                                        class="admin-form__input-text"
                                    >

                                    <button type="submit" class="admin-btn admin-btn--secondary">
                                        Guardar
                                    </button>
                                </div>
                            </form>
                        </td>

                        <td>$ {{ number_format((float) $item->subtotal_usd, 2, '.', ',') }}</td>
                        <td>Bs. {{ number_format((float) $item->subtotal_bs, 2, ',', '.') }}</td>

                        <td>
                            <form
                                method="POST"
                                action="{{ route('admin.quotes.items.destroy', $item) }}"
                                data-quote-ajax-form
                                data-confirm-message="¿Eliminar este producto?"
                            >
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