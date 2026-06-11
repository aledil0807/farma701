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
  @livewireStyles
</head>

<body>
  <div x-data="stockToast()" x-show="visible" x-transition class="stock-toast" x-text="message"></div>

  <x-header />

  <main>
    @if($banners->isNotEmpty())
      <section class="hero-carousel" x-data="heroCarousel()" x-init="init()">
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

    @if(isset($featuredProducts) && $featuredProducts->isNotEmpty())
      <section class="featured-month section-space">
        <div class="container-reduced">
          <div class="featured-products featured-products--carousel">
            <div class="featured-products__head">
              <div class="featured-products__title-wrap">
                <span class="discount-badge">
                  <i class="fa-solid fa-percent"></i>
                </span>
                <h2>Productos del mes</h2>
              </div>

              <div class="featured-carousel__controls">
                <button type="button" class="featured-carousel__btn" @click="$dispatch('featured-prev')">
                  <i class="fa-solid fa-angle-left"></i>
                </button>

                <button type="button" class="featured-carousel__btn" @click="$dispatch('featured-next')">
                  <i class="fa-solid fa-angle-right"></i>
                </button>
              </div>
            </div>

            <div class="featured-carousel" x-data="featuredCarousel({ total: {{ $featuredProducts->count() }} })"
              x-init="init()" @featured-prev.window="prev()" @featured-next.window="next()">
              <div class="featured-carousel__stage">
                @foreach($featuredProducts as $index => $product)
                  <article class="product-card featured-carousel__card" :class="cardClass({{ $index }})"
                    @click="goTo({{ $index }})">
                    @if($product->is_controlled)
                      <div class="product-card__controlled" tabindex="0" title="Este producto requiere récipe.">
                        <span class="product-card__controlled-dot"></span>
                        <div class="product-card__controlled-tooltip">
                          Este producto requiere récipe.
                        </div>
                      </div>
                    @endif

                    <div class="product-card__image">
                      <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                    </div>

                    <div class="product-card__body">
                      <div class="product-card__title-wrap">
                        <h3 class="product-card__title">{{ $product->name }}</h3>
                      </div>

                      <p class="product-card__brand">
                        {{ $product->laboratory?->name }}
                      </p>

                      <span class="product-card__status {{ $product->cantidad > 0 ? 'is-available' : 'is-unavailable' }}">
                        {{ $product->cantidad > 0 ? 'Disponible' : 'Agotado' }}
                      </span>

                      <p class="product-card__price">
                        $ {{ number_format($product->price, 2, '.', ',') }}
                      </p>

                      <div class="cart-inline-control" x-data="cartControl({
                                            productId: {{ $product->id }},
                                            initialQuantity: 0,
                                            maxStock: {{ (int) $product->cantidad }},
                                            productName: @js($product->name),
                                            addUrl: '{{ route('cart.add', $product) }}',
                                            incrementUrl: '{{ route('cart.increment', $product) }}',
                                            decrementUrl: '{{ route('cart.decrement', $product) }}'
                                        })">
                        <template x-if="quantity === 0">
                          <button type="button" class="product-card__button" @click.stop="add()">
                            Agregar al carrito
                          </button>
                        </template>

                        <template x-if="quantity > 0">
                          <div class="product-qty-control">
                            <button type="button" class="product-qty-control__btn" @click.stop="decrement()">−</button>
                            <span class="product-qty-control__value" x-text="quantity"></span>
                            <button type="button" class="product-qty-control__btn" @click.stop="increment()">+</button>
                          </div>
                        </template>
                      </div>
                    </div>
                  </article>
                @endforeach
              </div>

              <div class="featured-carousel__dots">
                @foreach($featuredProducts as $index => $product)
                  <button type="button" class="featured-carousel__dot" :class="{ 'is-active': current === {{ $index }} }"
                    @click="goTo({{ $index }})">
                  </button>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>
    @endif

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