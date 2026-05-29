@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1 class="admin-page__title">Banners</h1>
            <p class="admin-page__subtitle">Administra las imágenes del carrusel principal.</p>
        </div>

        <a href="{{ route('admin.banners.create') }}" class="admin-btn admin-btn--primary">
            Nuevo banner
        </a>
        <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--ghost">
            Volver al panel
        </a>
    </div>

    @if(session('success'))
        <div class="admin-alert admin-alert--success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-card">
        @if($banners->isEmpty())
            <p>No hay banners cargados todavía.</p>
        @else
            <div class="admin-banner-list">
                @foreach($banners as $banner)
                    <div class="admin-banner-item">
                        <div class="admin-banner-item__image">
                            <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}">
                        </div>

                        <div class="admin-banner-item__info">
                            <h3>{{ $banner->title ?: 'Sin título' }}</h3>
                            <p>Orden: {{ $banner->sort_order }}</p>
                            <p>Estado: {{ $banner->is_active ? 'Activo' : 'Inactivo' }}</p>
                            <p>Link: {{ $banner->link ?: 'Sin link' }}</p>
                        </div>

                        <div class="admin-banner-item__actions">
                            <a href="{{ route('admin.banners.edit', $banner) }}" class="admin-btn admin-btn--secondary">
                                Editar
                            </a>

                            <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('¿Eliminar este banner?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn--danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection