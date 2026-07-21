@extends('layouts.admin')
@section('content')
<div class="admin-page import-products-page">
    <div class="admin-page__header">
        <div>
            <h1 class="admin-page__title">Actualizar catálogo</h1>
            
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert admin-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="admin-alert admin-alert--danger">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="admin-alert admin-alert--danger">
            Revisa el archivo seleccionado e intenta nuevamente.
        </div>
    @endif

    <div class="admin-card import-products-card">
        <div class="import-products-card__icon">
            <i class="fa-solid fa-file-excel"></i>
        </div>

        <div class="import-products-card__content">
            <h2>Importar archivo Excel</h2>
            

            <form
                action="{{ route('import.process') }}"
                method="POST"
                enctype="multipart/form-data"
                class="import-products-form"
            >
                @csrf

                <div class="admin-form__group">
                    <label class="admin-form__label">Archivo Excel (.xlsx)</label>

                    <input
                        type="file"
                        name="file"
                        accept=".xlsx,.xls"
                        class="admin-form__input-text import-products-file-input"
                        required
                    >

                    @error('file')
                        <small class="admin-form__error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="import-products-actions">
                    <button type="submit" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-upload"></i>
                        Subir e importar
                    </button>

                    <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--secondary">
                        Volver al panel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection