@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1 class="admin-page__title">Laboratorios</h1>
            <p class="admin-page__subtitle">Administra nombres y logos de laboratorios.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert admin-alert--success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-card">
        <div class="admin-banner-list">
            @foreach($laboratories as $laboratory)
                <div class="admin-banner-item">
                    <div class="admin-banner-item__image">
                        <img src="{{ $laboratory->logo_url }}" alt="{{ $laboratory->name }}">
                    </div>

                    <div class="admin-banner-item__info">
                        <h3>{{ $laboratory->name }}</h3>
                    </div>

                    <div class="admin-banner-item__actions">
                        <a href="{{ route('admin.laboratories.edit', $laboratory) }}" class="admin-btn admin-btn--secondary">
                            Editar
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection