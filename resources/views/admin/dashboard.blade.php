<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <main style="padding: 30px;">
        <h1>Panel de administración</h1>
        <p>Bienvenido, {{ session('admin_email') }}</p>

        <div style="display:flex; gap:12px; margin-top:20px;">
            <a href="{{ route('products.create') }}" class="btn-view-more">Crear producto</a>
            <a href="{{ route('import.form') }}" class="btn-view-more">Importar catálogo</a>
            <a href="{{ route('admin.products.import-images.show') }}" class="btn-view-more">Importar imágenes</a>

            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="clear-cart-btn">Cerrar sesión</button>
            </form>
        </div>
    </main>
</body>
</html>