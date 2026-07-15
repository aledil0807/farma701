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
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ $assetVersion }}">

  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="icon" type="image/png" href="{{ asset('assets/img/Logo.png') }}">
  <script>
    window.cartInitial = {
      unitsCount: {{ app(\App\Services\CartService::class)->totals()['units_count'] ?? 0 }}
    };
  </script>

  <script defer src="{{ asset('js/main.js') }}?v={{ $assetVersion }}"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>

<body>
  <div x-data="stockToast()" x-show="visible" x-transition class="stock-toast" x-text="message"></div>

  <x-header />

  <main>
    @if($banners->isNotEmpty())
      <section class="hero-carousel container-reduced" x-data="heroCarousel()" x-init="init()">
        <div class="hero-carousel__track" :style="`transform: translateX(-${current * 100}%);`">
          @foreach($banners as $banner)
            <div class="hero-carousel__slide">
              @if($banner->link)
                <a href="{{ $banner->link }}">
                  <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?: 'Banner' }}">
                </a>
              @else
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?: 'Banner' }}">
              @endif
            </div>
          @endforeach
        </div>

        @if($banners->count() > 1)
          <button type="button" class="hero-carousel__arrow hero-carousel__arrow--prev" @click="prev()">‹</button>
          <button type="button" class="hero-carousel__arrow hero-carousel__arrow--next" @click="next()">›</button>

          <div class="hero-carousel__dots">
            @foreach($banners as $index => $banner)
              <button type="button" class="hero-carousel__dot" :class="{ 'is-active': current === {{ $index }} }"
                @click="goTo({{ $index }})"></button>
            @endforeach
          </div>
        @endif
      </section>
    @endif
    <section class="delivery-strip">
      <div class="container container-strip">
        <div class="delivery-strip__content">
          <div class="delivery-strip__left">
            <span>Delivery gratis</span>
          </div>

          <div class="delivery-strip__image">
            <img src="{{ asset('assets/img/moto-delivery.png') }}" alt="Delivery gratis">
          </div>

          <div class="delivery-strip__right">
            <strong>En compras a partir de 20$</strong>
            <span>Aplica a zonas céntricas</span>
          </div>
        </div>
      </div>
    </section>
    <!--<section class="hero">
      <div class="hero__slides">
        <article class="hero__slide is-active">
          <button class="hero__arrow hero__arrow--left" aria-label="Anterior"><i
              class="fa-solid fa-arrow-left"></i></button>
          <div class="hero__background">
            <img src="assets/img/banner-principal.jpeg" alt="Banner principal">
          </div>
          <button class="hero__arrow hero__arrow--right" aria-label="Siguiente"><i
              class="fa-solid fa-arrow-right"></i></button>
        </article>
      </div>
    </section>-->

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
    </section>-->

    <section class="featured-and-steps">
      <div class="container featured-and-steps__grid">

        <div class="featured-products card-panel">

          <div class="featured-products__head">
            <div class="featured-products__title-wrap">
              <span class="discount-badge">
                <i class="fa-solid fa-percent"></i>
              </span>
              <h2>Productos del mes</h2>
            </div>

            @php
              $serverNow = now();
              $deadline = now()->endOfMonth();

              $serverNowMs = $serverNow->timestamp * 1000;
              $deadlineMs = $deadline->timestamp * 1000;
            @endphp

            <div class="countdown" data-countdown-monthly data-server-now-ms="{{ $serverNowMs }}"
              data-deadline-ms="{{ $deadlineMs }}">
              <div><strong data-days>00</strong><span>Días</span></div>
              <div><strong data-hours>00</strong><span>Horas</span></div>
              <div><strong data-minutes>00</strong><span>Minutos</span></div>
            </div>
          </div>

          <div class="monthly-carousel" data-monthly-carousel>
            <button type="button" class="monthly-carousel__arrow monthly-carousel__arrow--prev" data-carousel-prev
              aria-label="Producto anterior">
              <i class="fa-solid fa-angle-left"></i>
            </button>

            <div class="monthly-carousel__viewport">
              <livewire:product-search mode="monthly" layout="carousel" />
            </div>

            <button type="button" class="monthly-carousel__arrow monthly-carousel__arrow--next" data-carousel-next
              aria-label="Producto siguiente">
              <i class="fa-solid fa-angle-right"></i>
            </button>
          </div>

        </div>

        <aside class="steps-card">
          <h2>¿Cómo hacer<br>tu pedido?</h2>

          <ol>
            <li>Selecciona tus productos</li>
            <li>Completa tus datos</li>
            <li>Confirma tu compra</li>
          </ol>

          <div class="steps-card__character" aria-hidden="true">
            <picture>
              <source media="(min-width: 701px) and (max-width: 1100px)"
                srcset="{{ asset('assets/img/sr-ricardo-muñeco.png') }}">

              <img src="{{ asset('assets/img/sr-ricardo-muñeco-2.png') }}" alt="">
            </picture>
          </div>
        </aside>

      </div>
    </section>

    <section class="ally-labs section-space">
      <div class="container">
        <h2 class="section-title">Laboratorios aliados</h2>

        <div class="labs-marquee" x-data="labsMarquee()" x-init="init()" x-ref="marquee">
          <div class="labs-marquee__track" x-ref="track">
            @foreach($allyLabs as $lab)
              <a class="lab-card" href="{{ route('laboratories.show', $lab->id) }}">
                <span class="lab-card__logo">
                  <img src="{{ $lab->logo_url }}" alt="{{ $lab->name }}">
                </span>
                <span class="lab-card__name">{{ $lab->name }}</span>
              </a>
            @endforeach

            @foreach($allyLabs as $lab)
              <a class="lab-card" href="{{ route('laboratories.show', $lab->id) }}">
                <span class="lab-card__logo">
                  <img src="{{ $lab->logo_url }}" alt="{{ $lab->name }}">
                </span>
                <span class="lab-card__name">{{ $lab->name }}</span>
              </a>
            @endforeach
          </div>
        </div>
      </div>
      <!-- <x-floating-cart /> -->
    </section>

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
      <a href="{{ route('admin.login') }}" aria-label="Carrito" class="cart-link">
        <p>Farmacia 701, C.A</p>
      </a>
    </div>
  </footer>


  @livewireScripts

</body>

</html>