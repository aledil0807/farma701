<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Farmacia 701</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @livewireStyles
</head>
<body>
    <header>
        <h1>Panel Admin</h1>
    </header>

    <main>
        @yield('content')
    </main>

    @livewireScripts
    <script src="{{ asset('js/main.js') }}"></script>
</body>
</html>