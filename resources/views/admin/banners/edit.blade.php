@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1 class="admin-page__title">Editar banner</h1>
            <p class="admin-page__subtitle">Actualiza la información del banner.</p>
        </div>

        <a href="{{ route('admin.banners.index') }}" class="admin-btn admin-btn--ghost">
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
        <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data" class="admin-form">
            @csrf
            @method('PUT')

            <div class="admin-form__group">
                <label class="admin-form__label" for="title">Título</label>
                <input class="admin-form__input-text" type="text" id="title" name="title" value="{{ old('title', $banner->title) }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="link">Link</label>
                <input class="admin-form__input-text" type="text" id="link" name="link" value="{{ old('link', $banner->link) }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="sort_order">Orden</label>
                <input class="admin-form__input-text" type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="image">Cambiar imagen</label>
                <input class="admin-form__input-file" type="file" id="image" name="image">
            </div>

            <div class="admin-form__group">
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" style="max-width: 320px; border-radius: 16px;">
            </div>

            <div class="admin-form__group">
                <label>
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $banner->is_active) ? 'checked' : '' }}>
                    Activo
                </label>
            </div>

            <div class="admin-form__actions">
                <button type="submit" class="admin-btn admin-btn--primary">Actualizar banner</button>
            </div>
        </form>
    </div>
</div>
@endsection