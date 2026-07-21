<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Farmacia 701</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ $assetVersion ?? '1.0.0' }}">
</head>

<body class="admin-login-page">
    <main class="admin-login-shell">
        <section class="admin-login-visual" aria-hidden="true">
            <div class="admin-login-visual__glow admin-login-visual__glow--one"></div>
            <div class="admin-login-visual__glow admin-login-visual__glow--two"></div>
            <div class="admin-login-visual__cross admin-login-visual__cross--one">+</div>
            <div class="admin-login-visual__cross admin-login-visual__cross--two">+</div>
        </section>

        <section class="admin-login-content">
            <div class="admin-login-card">
                <div class="admin-login-card__logo">
                    <img src="{{ asset('assets/img/Logo.png') }}" alt="Farmacia 701">
                </div>

                <h1>Iniciar sesión</h1>

                @if($errors->any())
                    <div class="admin-login-error">
                        Usuario o contraseña incorrectos.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.submit') }}" class="admin-login-form">
                    @csrf

                    <div class="admin-login-field">
                        <span class="admin-login-field__icon">
                            <i class="fa-solid fa-user"></i>
                        </span>

                        <input
                            type="text"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Usuario o correo electrónico"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="admin-login-field">
                        <span class="admin-login-field__icon">
                            <i class="fa-solid fa-lock"></i>
                        </span>

                        <input
                            id="adminLoginPassword"
                            type="password"
                            name="password"
                            placeholder="Contraseña"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="admin-login-field__toggle"
                            onclick="toggleAdminLoginPassword()"
                            aria-label="Mostrar u ocultar contraseña"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    <button type="submit" class="admin-login-submit">
                        Ingresar
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script>
        function toggleAdminLoginPassword() {
            const input = document.getElementById('adminLoginPassword');

            if (!input) return;

            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>