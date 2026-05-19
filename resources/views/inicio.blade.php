<?php
$mainCategories = [
  ["key" => "salud-medicamentos", "label" => "Salud y\nMedicamentos", "icon" => "assets/img/salud-y-medicamentos.png", "link" => "#"],
  ["key" => "cuidado-personal", "label" => "Cuidado\nPersonal", "icon" => "assets/img/cuidado-personal.png", "link" => "#"],
  ["key" => "nutricion", "label" => "Nutrición", "icon" => "assets/img/nutrición.png", "link" => "#"],
  ["key" => "suministros", "label" => "Suministros\nMédicos", "icon" => "assets/img/suministros-medicos.png", "link" => "#"],
  ["key" => "hogar", "label" => "Hogar", "icon" => "assets/img/hogar.png", "link" => "#"],
  ["key" => "equipos", "label" => "Equipos\nMédicos", "icon" => "assets/img/equipos-medicos.png", "link" => "#"],
  ["key" => "linea-infantil", "label" => "Línea Infantil", "icon" => "assets/img/linea-infantil.png", "link" => "#"],
  ["key" => "alimentos-bebidas", "label" => "Alimentos y\nBebidas", "icon" => "assets/img/alimentos-y-bebidas.png", "link" => "#"],
];

$monthlyProducts = [
  ["title" => "Acetaminofen 650mg cap x 10", "image" => "assets/img/acetaminofen.png", "link" => "#", 'price_bs' => '4.235,21', 'price_usd' => '9.95', 'brand' => 'CALOX', 'available' => true],
  ["title" => "Producto 2", "image" => "assets/img/acetaminofen.png", "link" => "#", 'price_bs' => '4.235,21', 'price_usd' => '9.95', 'brand' => 'Buka', 'available' => true],
  ["title" => "Producto 3", "image" => "assets/img/acetaminofen.png", "link" => "#", 'price_bs' => '4.235,21', 'price_usd' => '9.95', 'brand' => 'Buka', 'available' => true],
  ["title" => "Producto 4", "image" => "assets/img/acetaminofen.png", "link" => "#", 'price_bs' => '4.235,21', 'price_usd' => '9.95', 'brand' => 'Buka', 'available' => true],
];

$allyLabs = [
  ["name" => "DAC 55", "logo" => "assets/img/farmacia1.png", "link" => "#"],
  ["name" => "Fito Sana", "logo" => "assets/img/farmacia2.png", "link" => "#"],
  ["name" => "Calox", "logo" => "assets/img/farmacia3.png", "link" => "#"],
  ["name" => "CCM", "logo" => "assets/img/farmacia4.png", "link" => "#"],
  ["name" => "BioTech", "logo" => "assets/img/farmacia5.png", "link" => "#"],
  ["name" => "ACM", "logo" => "assets/img/farmacia6.png", "link" => "#"],
  ["name" => "Drotafarma", "logo" => "assets/img/farmacia7.png", "link" => "#"],
];

$catalogBlocks = [
  ["title" => "Catálogo 1", "image" => "assets/img/catalogo-1.jpg", "link" => "#"],
  ["title" => "Catálogo 2", "image" => "assets/img/catalogo-2.jpg", "link" => "#"],
  ["title" => "Catálogo 3", "image" => "assets/img/catalogo-3.jpg", "link" => "#"],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Farmacia 701 | Inicio</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="icon" type="image/png" href="{{ asset('assets/img/Logo.png') }}">
  <script>
    window.cartInitial = {
      unitsCount: {{ app(\App\Services\CartService::class)->totals()['units_count'] ?? 0 }}
    };
  </script>
  <script src="{{ asset('js/main.js') }}"></script>
  @livewireStyles
</head>

<body>
  <div x-data="stockToast()" x-show="visible" x-transition class="stock-toast" x-text="message"></div>
  <header class="site-header">
    <div class="topbar">
      <div class="container topbar__content">
        <p>Farmacia 701 ¡Somos tus aliados en salud!</p>
      </div>
    </div>

    <div class="header-main">
      <div class="container header-main__content">
        <a href="#" class="brand">
          <img src="assets/img/logo-nuevo.jpeg" alt="Farmacia 701">
        </a>

        <form class="search-bar" action="{{ route('search.results') }}" method="get">
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar productos, marcas o categorías">
          <button type="submit" aria-label="Buscar">
            <i class="fa-solid fa-magnifying-glass"></i>
          </button>
        </form>

        <div class="header-actions">

          <div x-data>
            <a href="{{ route('cart.index') }}" aria-label="Carrito" class="cart-link">
              <i class="fa-solid fa-cart-shopping"></i>

              <template x-if="$store.cart.unitsCount > 0">
                <span class="cart-badge" x-text="$store.cart.unitsCount"></span>
              </template>
            </a>
          </div>
        </div>
      </div>
    </div>

    <nav class="main-nav">
      <div class="container">
        <ul class="nav-menu">
          <li><a href="#">Inicio</a></li>

          <!--<li class="has-dropdown">
            <button class="dropdown-toggle" type="button">
              Laboratorios <i class="fa-solid fa-angle-down"></i>
            </button>
            <ul class="dropdown-menu">
              <li><a href="#">Calox</a></li>
              <li><a href="#">Pharmetique</a></li>
              <li><a href="#">Rowe</a></li>
              <li><a href="#">Valmorca</a></li>
              <li><a href="#">Farma</a></li>
            </ul>
          </li>

          <li class="has-dropdown">
            <button class="dropdown-toggle" type="button">
              Categorías <i class="fa-solid fa-angle-down"></i>
            </button>
            <ul class="dropdown-menu">
              <li><a href="#">Salud y Medicamentos</a></li>
              <li><a href="#">Cuidado Personal</a></li>
              <li><a href="#">Nutrición</a></li>
              <li><a href="#">Suministros Médicos</a></li>
              <li><a href="#">Hogar</a></li>
              <li><a href="#">Equipos Médicos</a></li>
              <li><a href="#">Línea Infantil</a></li>
              <li><a href="#">Alimentos y Bebidas</a></li>
            </ul>
          </li>

          <li class="has-dropdown">
            <button class="dropdown-toggle" type="button">
              Directorio Médico <i class="fa-solid fa-angle-down"></i>
            </button>
            <ul class="dropdown-menu">
              <li><a href="#">Medicina General</a></li>
              <li><a href="#">Pediatría</a></li>
              <li><a href="#">Cardiología</a></li>
              <li><a href="#">Ginecología</a></li>
              <li><a href="#">Traumatología</a></li>
            </ul>
          </li>-->
        </ul>
      </div>
    </nav>
  </header>

  <main>
    <section class="hero">
      <div class="hero__slides">
        <article class="hero__slide is-active">
          <!--<button class="hero__arrow hero__arrow--left" aria-label="Anterior"><i
              class="fa-solid fa-arrow-left"></i></button>-->
          <div class="hero__background">
            <img src="assets/img/banner-principal.jpeg" alt="Banner principal">
          </div>
          <!--<button class="hero__arrow hero__arrow--right" aria-label="Siguiente"><i
              class="fa-solid fa-arrow-right"></i></button>-->
        </article>
      </div>
    </section>

    <!--<section class="main-categories section-space">
      <div class="container">
        <h2 class="section-title">Categorías principales</h2>
        <div class="categories-grid">
          @foreach($mainCategories as $category)
            <a class="category-card" href="<?= htmlspecialchars($category['link']) ?>">
              <span class="category-card__icon">
                <img src="<?= htmlspecialchars($category['icon']) ?>"
                  alt="<?= htmlspecialchars(str_replace("\n", ' ', $category['label'])) ?>">
              </span>
              <span class="category-card__label"><?= nl2br(htmlspecialchars($category['label'])) ?></span>
            </a>
          @endforeach
        </div>
      </div>
    </section>

    <section class="featured-and-steps">
      <div class="container featured-and-steps__grid">
        <div class="featured-products card-panel">
          <div class="featured-products__head">
            <div class="featured-products__title-wrap">
              <span class="discount-badge"><i class="fa-solid fa-percent"></i></span>
              <h2>Productos del mes</h2>
            </div>
            <div class="countdown" data-deadline="2026-12-31T23:59:59">
              <div><strong data-days>00</strong><span>Días</span></div>
              <div><strong data-hours>00</strong><span>Horas</span></div>
              <div><strong data-minutes>00</strong><span>Minutos</span></div>
            </div>
          </div>

          <div class="products-grid">
            @foreach($monthlyProducts as $product)
              <a class="product-card" href="<?= htmlspecialchars($product['link']) ?>">
                <div class="product-card__image">
                  <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
                </div>

                <div class="product-card__body">
                  <div class="product-card__title-wrap">
                    <h3 class="product-card__title"><?= htmlspecialchars($product['title']) ?></h3>
                  </div>

                  <p class="product-card__brand"><?= htmlspecialchars($product['brand']) ?></p>

                  <span
                    class="product-card__status <?= !empty($product['available']) ? 'is-available' : 'is-unavailable' ?>">
                    <?= !empty($product['available']) ? '¡Disponible!' : 'No disponible' ?>
                  </span>

                  <p class="product-card__price">
                    Bs. <?= htmlspecialchars($product['price_bs']) ?> | $ <?= htmlspecialchars($product['price_usd']) ?>
                  </p>

                  <span class="product-card__button">Agregar al carrito</span>
                </div>
              </a>
            @endforeach
          </div>
        </div>

        <aside class="steps-card">
          <h2>¿Cómo hacer<br>tu pedido?</h2>
          <ol>
            <li>Selecciona tus productos</li>
            <li>Completa tus datos</li>
            <li>Confirma tu compra</li>
          </ol>
        </aside>
      </div>
    </section>

    <section class="ally-labs section-space">
      <div class="container">
        <h2 class="section-title">Laboratorios aliados</h2>
        <div class="labs-grid">
          @foreach($allyLabs as $lab)
            <a class="lab-card" href="<?= htmlspecialchars($lab['link']) ?>">
              <span class="lab-card__logo">
                <img src="<?= htmlspecialchars($lab['logo']) ?>" alt="<?= htmlspecialchars($lab['name']) ?>">
              </span>
              <span class="lab-card__name"><?= htmlspecialchars($lab['name']) ?></span>
            </a>
          @endforeach
        </div>
      </div>
    </section>-->

    <!--<section class="catalog section-space section-divider-top section-divider-bottom">
      <div class="container">
        <div class="catalog-grid">
          @foreach($catalogBlocks as $catalog)
            <a class="catalog-card" href="<?= htmlspecialchars($catalog['link']) ?>">
              <img src="<?= htmlspecialchars($catalog['image']) ?>" alt="<?= htmlspecialchars($catalog['title']) ?>">
            </a>
          @endforeach
        </div>
        <h2 class="section-title section-title--bottom">Catálogo</h2>
      </div>
    </section>-->
    <livewire:product-search />

  </main>

  <footer class="site-footer">
    <div class="container search-footer__content">
      <p>Encuentra todos tus medicamentos, productos de salud, cuidado personal y suplementos deportivos.</p>
      <p>Av. 17 de diciembre C/C Calle Madrid, Local # 28, Séctor Negro Primero, Parroquia catedral, Frente a la
        clínica Santa Ana, Ciudad Bolívar - Venezuela.</p>
      <p>¡Somos tus Aliados en Salud!</p>
      <a href="{{ route('admin.login') }}" aria-label="Carrito" class="cart-link"><p>Farmacia 701, C.A</p></a>
    </div>
  </footer>


  @livewireScripts
</body>

</html>