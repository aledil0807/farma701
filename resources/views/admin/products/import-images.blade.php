@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1 class="admin-page__title">Importar imágenes de productos</h1>
            <p class="admin-page__subtitle">
                Carga varias imágenes al mismo tiempo. El sistema asociará cada archivo con el producto correspondiente según su nombre.
            </p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--ghost">
            Volver al panel
        </a>
    </div>

    @if(session('message'))
        <div class="admin-alert admin-alert--success">
            {{ session('message') }}
        </div>
    @endif

    @if(session('unmatched_images') && count(session('unmatched_images')) > 0)
        <div class="admin-alert admin-alert--warning">
            <strong>No se encontraron productos para estas imágenes:</strong>
            <ul class="admin-alert__list">
                @foreach(session('unmatched_images') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
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
        <form action="{{ route('admin.products.import-images') }}" method="POST" enctype="multipart/form-data" class="admin-form">
            @csrf

            <div class="admin-form__group">
                <label for="images" class="admin-form__label">Selecciona las imágenes</label>

                <div class="admin-upload-box">
                    <input id="images" type="file" name="images[]" multiple class="admin-form__input-file">
                    <p class="admin-upload-box__title">Selecciona los archivos</p>
                    <p class="admin-upload-box__text">
                        Formatos permitidos: JPG, JPEG y PNG. Puedes subir varias imágenes a la vez.
                    </p>
                </div>
            </div>

            <div class="admin-form__actions">
                <button type="submit" class="admin-btn admin-btn--primary">
                    Cargar imágenes
                </button>

                <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection