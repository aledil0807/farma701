<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | Farmacia 701</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=1.0.2">
</head>
<body>
    <main class="admin-login-page">
        <div class="admin-login-card">
            <h1>Acceso administrador</h1>

            @if ($errors->any())
                <div class="cart-alert cart-alert--error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" class="admin-login-form">
                @csrf

                <div class="cart-field">
                    <label for="email">Correo</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}">
                </div>

                <div class="cart-field">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password">
                </div>

                <button type="submit" class="checkout-btn">Iniciar sesión</button>
            </form>
        </div>
    </main>
</body>
</html>