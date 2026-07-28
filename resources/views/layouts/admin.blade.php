<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Farmacia 701</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ $assetVersion }}">
    <script defer src="{{ asset('js/main.js') }}?v={{ $assetVersion }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    @livewireStyles
</head>

<body class="admin-panel-body">
    <div class="admin-panel-shell">
        @include('admin.partials.sidebar')

        <main class="admin-panel-main">
            @yield('content')
        </main>
    </div>
</body>

</html>