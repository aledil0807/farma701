@extends('layouts.admin')

@section('content')
<div
    class="admin-page"
    id="quoteBuilder"
    data-product-search-url="{{ route('admin.quotes.products.search') }}"
>
    <div class="admin-page__header">
        <div>
            <h1 class="admin-page__title">Presupuesto {{ $quote->quote_number }}</h1>
            <p class="admin-page__subtitle">
                Creador: {{ $quote->employee_name ?? 'Usuario no indicado' }}
            </p>
        </div>

        <a href="{{ route('admin.quotes.index') }}" class="admin-btn admin-btn--secondary">
            Volver
        </a>
    </div>

    @if(session('success'))
        <div class="admin-alert admin-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="admin-alert admin-alert--danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('admin.quotes.partials.total-card', ['quote' => $quote])

    <div class="admin-card">
        <h2>Agregar conjunto</h2>

        <form method="POST" action="{{ route('admin.quotes.groups.store', $quote) }}">
            @csrf

            <div class="admin-form-grid">
                <div class="admin-form__group">
                    <label class="admin-form__label">Nombre del conjunto</label>
                    <input
                        type="text"
                        name="name"
                        class="admin-form__input-text"
                        placeholder="Ej: Tratamiento gripe"
                        required
                    >
                </div>

                <div class="admin-form__group admin-form__group--button">
                    <button type="submit" class="admin-btn admin-btn--primary">
                        Agregar conjunto
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div data-quote-groups-list>
        @foreach($quote->groups as $group)
            @include('admin.quotes.partials.group-card', ['group' => $group])
        @endforeach
    </div>
</div>
@endsection