@extends('layouts.admin')

@section('content')
<h1>Importar imágenes de productos</h1>

@if(session('message'))
    <div>{{ session('message') }}</div>
@endif

@if(session('errors') && count(session('errors')) > 0)
    <div>
        <strong>No se encontraron productos para estas imágenes:</strong>
        <ul>
            @foreach(session('errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.products.import-images') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="images[]" multiple>
    <button type="submit">Cargar imágenes</button>
</form>
@endsection