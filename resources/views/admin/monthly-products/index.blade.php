@extends('layouts.admin')

@section('content')
    <div class="admin-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Productos del mes</h1>
                <p class="admin-page__subtitle">
                    Selecciona los productos que aparecerán en la sección principal de productos del mes.
                </p>
            </div>

            <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--secondary">
                Volver al panel
            </a>
        </div>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.monthly-products.update') }}" class="admin-card"
            id="monthlyProductsForm" data-search-url="{{ route('admin.monthly-products.search') }}">
            
            @csrf

            <div id="monthlySelectedInputs">
                @foreach($selectedProducts as $product)
                    <input type="hidden" name="monthly_products[]" value="{{ $product->id }}"
                        data-selected-product-input="{{ $product->id }}">
                @endforeach
            </div>

            <h2 class="admin-section-title">Seleccionados para productos del mes</h2>

            @if($selectedProducts->isEmpty())
                <p class="admin-empty-text">
                    Todavía no has seleccionado productos del mes.
                </p>
            @else
                <div class="admin-monthly-list">
                    @foreach($selectedProducts as $product)
                        <div class="admin-monthly-item admin-monthly-item--selected">
                            <label class="admin-monthly-item__check">
                                <input type="checkbox" checked data-monthly-product-checkbox data-product-id="{{ $product->id }}">
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
                                <input type="number" name="monthly_order[{{ $product->id }}]" value="{{ $product->monthly_order }}"
                                    min="1" placeholder="-">
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <hr class="admin-divider">

            <h2 class="admin-section-title">Agregar productos</h2>

            <div class="admin-form__group" style="margin-bottom: 18px;">
                <label class="admin-form__label" for="monthlyAjaxSearch">Buscar producto</label>

                <input id="monthlyAjaxSearch" type="text" class="admin-form__input-text"
                    placeholder="Buscar por nombre, laboratorio o categoría..." data-monthly-search-input>
            </div>

            <div class="admin-monthly-list" id="monthlyProductsResults" data-monthly-results>

            </div>

            <div class="admin-form__actions admin-monthly-actions">
                <button type="submit" class="admin-btn admin-btn--primary">
                    Guardar productos del mes
                </button>

                <button type="button" class="admin-btn admin-btn--secondary" data-monthly-load-more style="display: none;">
                    Ver más productos
                </button>
            </div>
        </form>
    </div>
@endsection