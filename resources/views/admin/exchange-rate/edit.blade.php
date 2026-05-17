@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1 class="admin-page__title">Tasa de cambio manual</h1>
            <p class="admin-page__subtitle">
                Desde aquí puedes definir manualmente la tasa USD a bolívares que usará el sistema.
            </p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--ghost">
            Volver al panel
        </a>
    </div>

    @if(session('success'))
        <div class="admin-alert admin-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="admin-alert admin-alert--error">
            <strong>Se encontraron errores:</strong>
            <ul class="admin-alert__list">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="admin-card">
        <form action="{{ route('admin.exchange-rate.update') }}" method="POST" class="admin-form">
            @csrf

            <div class="admin-form__group">
                <label for="rate" class="admin-form__label">Tasa actual USD → Bs</label>
                <input
                    id="rate"
                    type="number"
                    name="rate"
                    step="0.0001"
                    min="0.0001"
                    value="{{ old('rate', $currentRate?->rate) }}"
                    class="admin-form__input-text"
                    placeholder="Ejemplo: 128.5500"
                >
            </div>

            @if($currentRate)
                <div class="admin-rate-info">
                    <p><strong>Tasa activa actual:</strong> {{ number_format($currentRate->rate, 4, ',', '.') }}</p>
                    <!-- <p><strong>Fuente:</strong> {{ $currentRate->source }}</p> -->
                    <!-- <p><strong>Fecha efectiva:</strong> {{ $currentRate->effective_date }}</p> -->
                </div>
            @endif

            <div class="admin-form__actions">
                <button type="submit" class="admin-btn admin-btn--primary">
                    Guardar tasa
                </button>

                <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection