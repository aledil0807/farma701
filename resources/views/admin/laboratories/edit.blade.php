@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1 class="admin-page__title">Editar laboratorio</h1>
            <p class="admin-page__subtitle">Actualiza el nombre y el logo.</p>
        </div>

        <a href="{{ route('admin.laboratories.index') }}" class="admin-btn admin-btn--ghost">
            Volver
        </a>
    </div>

    @if($errors->any())
        <div class="admin-alert admin-alert--error">
            <ul class="admin-alert__list">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="admin-card">
        <form action="{{ route('admin.laboratories.update', $laboratory) }}" method="POST" enctype="multipart/form-data" class="admin-form">
            @csrf
            @method('PUT')

            <div class="admin-form__group">
                <label class="admin-form__label" for="name">Nombre</label>
                <input class="admin-form__input-text" type="text" id="name" name="name" value="{{ old('name', $laboratory->name) }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="logo">Logo</label>
                <input class="admin-form__input-file" type="file" id="logo" name="logo">
            </div>

            <div class="admin-form__group">
                <img src="{{ $laboratory->logo_url }}" alt="{{ $laboratory->name }}" style="max-width: 180px; border-radius: 16px;">
            </div>

            <div class="admin-form__actions">
                <button type="submit" class="admin-btn admin-btn--primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection