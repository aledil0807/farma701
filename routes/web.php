<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ImageImportController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\LaboratoryController;
use App\Http\Controllers\Admin\MonthlyProductController;
use App\Http\Controllers\Admin\QuoteController;
use App\Http\Controllers\Admin\ControlledProductReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CustomerProductRequestController;



use App\Models\Banner;
use App\Models\Laboratory;
use App\Models\Product;




//Ruta para ver el catálogo
Route::get('/', function () {


    $banners = Banner::where('is_active', true)
        ->orderBy('sort_order')
        ->orderByDesc('id')
        ->get();

    $featuredLabs = [
        'CALOX',
        'PHARMETIQUE',
        'ROWE',
        'VALMORCA',
        'FARMA',
        'MEGALABS',
        'DROTAFARMA',
        'DISTRILAB',
    ];
    $featuredProductIds = [355, 321, 522];

    $monthlyProducts = Product::with(['laboratory', 'category'])
        ->where('is_monthly_product', true)
        ->orderByRaw('monthly_order IS NULL, monthly_order ASC')
        ->orderBy('name')
        ->get();

    $featuredProducts = Product::with(['category', 'laboratory'])
        ->whereIn('id', $featuredProductIds)
        ->get()
        ->sortBy(function ($product) use ($featuredProductIds) {
            return array_search($product->id, $featuredProductIds);
        })
        ->values();

    $featuredLabs = [
        'CALOX',
        'PHARMETIQUE',
        'ROWE',
        'VALMORCA',
        'FARMA',
        'MEGALABS',
        'DROTAFARMA',
        'DISTRILAB',
    ];

    $allyLabs = Laboratory::visible()
        ->get()
        ->filter(function ($lab) use ($featuredLabs) {
            return in_array(
                mb_strtoupper(trim($lab->name)),
                $featuredLabs,
                true
            );
        })
        ->sortBy(function ($lab) use ($featuredLabs) {
            return array_search(
                mb_strtoupper(trim($lab->name)),
                $featuredLabs,
                true
            );
        })
        ->values();


    return view('inicio', compact('banners', 'allyLabs', 'featuredProducts', 'monthlyProducts'));
})->name('home');

Route::get('/busqueda', function () {
    return view('busqueda');
})->name('search.results');

Route::get('/laboratorios/{laboratory}', function (Laboratory $laboratory) {
    return view('laboratories.show', compact('laboratory'));
})->name('laboratories.show');






Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/agregar/{product}', [CartController::class, 'add'])->name('cart.add');
Route::post('/carrito/incrementar/{product}', [CartController::class, 'increment'])->name('cart.increment');
Route::post('/carrito/disminuir/{product}', [CartController::class, 'decrement'])->name('cart.decrement');
Route::post('/carrito/eliminar/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/carrito/vaciar', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/carrito/procesar', [CartController::class, 'checkout'])->name('cart.checkout');

Route::post('/ajax/carrito/agregar/{product}', [CartController::class, 'ajaxAdd'])->name('cart.ajax.add');
Route::post('/ajax/carrito/incrementar/{product}', [CartController::class, 'ajaxIncrement'])->name('cart.ajax.increment');
Route::post('/ajax/carrito/disminuir/{product}', [CartController::class, 'ajaxDecrement'])->name('cart.ajax.decrement');
Route::get('/ajax/carrito/resumen', [CartController::class, 'ajaxSummary'])->name('cart.ajax.summary');
Route::post('/ajax/carrito/eliminar/{product}', [CartController::class, 'ajaxRemove'])->name('cart.ajax.remove');
Route::post('/ajax/carrito/vaciar', [CartController::class, 'ajaxClear'])->name('cart.ajax.clear');
Route::get('/ajax/carrito/detalle', [CartController::class, 'ajaxDetail'])->name('cart.ajax.detail');


Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('admin.auth')->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::get('/productos-del-mes', [MonthlyProductController::class, 'index'])
        ->name('admin.monthly-products.index');

    Route::post('/productos-del-mes', [MonthlyProductController::class, 'update'])
        ->name('admin.monthly-products.update');

    Route::get('/productos-del-mes/buscar', [MonthlyProductController::class, 'search'])
        ->name('admin.monthly-products.search');

    Route::get('/laboratorios', [LaboratoryController::class, 'index'])->name('admin.laboratories.index');
    Route::get('/laboratorios/{laboratory}/editar', [LaboratoryController::class, 'edit'])->name('admin.laboratories.edit');
    Route::put('/laboratorios/{laboratory}', [LaboratoryController::class, 'update'])->name('admin.laboratories.update');

    Route::get('/productos/crear', [ProductController::class, 'create'])->name('products.create');
    Route::post('/productos/guardar', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/import-images', [ImageImportController::class, 'show'])->name('admin.products.import-images.show');
    Route::post('/products/import-images', [ImageImportController::class, 'import'])->name('admin.products.import-images');
    Route::get('/exchange-rate', [ExchangeRateController::class, 'edit'])->name('admin.exchange-rate.edit');
    Route::post('/exchange-rate', [ExchangeRateController::class, 'update'])->name('admin.exchange-rate.update');

    Route::get('/importar', [ImportController::class, 'showForm'])->name('import.form');
    Route::post('/importar', [ImportController::class, 'import'])->name('import.process');
    Route::get('/banners', [BannerController::class, 'index'])->name('admin.banners.index');
    Route::get('/banners/create', [BannerController::class, 'create'])->name('admin.banners.create');
    Route::post('/banners', [BannerController::class, 'store'])->name('admin.banners.store');
    Route::get('/banners/{banner}/edit', [BannerController::class, 'edit'])->name('admin.banners.edit');
    Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('admin.banners.update');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('admin.banners.destroy');

    Route::get('/presupuestos', [QuoteController::class, 'index'])
        ->name('admin.quotes.index');

    Route::post('/presupuestos', [QuoteController::class, 'store'])
        ->name('admin.quotes.store');

    Route::get('/presupuestos/productos/buscar', [QuoteController::class, 'searchProducts'])
        ->name('admin.quotes.products.search');

    Route::get('/presupuestos/{quote}/edit', [QuoteController::class, 'edit'])
        ->name('admin.quotes.edit');

    Route::put('/presupuestos/{quote}', [QuoteController::class, 'update'])
        ->name('admin.quotes.update');

    Route::delete('/presupuestos/{quote}', [QuoteController::class, 'destroy'])
        ->name('admin.quotes.destroy');

    Route::post('/presupuestos/{quote}/grupos', [QuoteController::class, 'storeGroup'])
        ->name('admin.quotes.groups.store');

    Route::put('/presupuestos/grupos/{group}', [QuoteController::class, 'updateGroup'])
        ->name('admin.quotes.groups.update');

    Route::delete('/presupuestos/grupos/{group}', [QuoteController::class, 'destroyGroup'])
        ->name('admin.quotes.groups.destroy');

    Route::post('/presupuestos/grupos/{group}/items', [QuoteController::class, 'storeItem'])
        ->name('admin.quotes.items.store');

    Route::put('/presupuestos/items/{item}', [QuoteController::class, 'updateItem'])
        ->name('admin.quotes.items.update');

    Route::delete('/presupuestos/items/{item}', [QuoteController::class, 'destroyItem'])
        ->name('admin.quotes.items.destroy');

    Route::get('/productos-controlados', [ControlledProductReportController::class, 'index'])
        ->name('admin.controlled-products.index');



    Route::post('/productos-controlados', [ControlledProductReportController::class, 'store'])
        ->name('admin.controlled-products.store');

    Route::post('/productos-controlados/mes', [ControlledProductReportController::class, 'storeMonth'])
        ->name('admin.controlled-products.month.store');

    Route::get('/productos-controlados/mes/{month}', [ControlledProductReportController::class, 'showMonth'])
        ->name('admin.controlled-products.month.show')
        ->where('month', '[0-9]{4}-[0-9]{2}');

    Route::get('/productos-controlados/mes/{month}/pdf', [ControlledProductReportController::class, 'exportMonthPdf'])
        ->name('admin.controlled-products.month.pdf')
        ->where('month', '[0-9]{4}-[0-9]{2}');

    Route::get('/productos-controlados/{controlledProduct}', [ControlledProductReportController::class, 'show'])
        ->name('admin.controlled-products.show');

    Route::delete('/productos-controlados/{controlledProduct}', [ControlledProductReportController::class, 'destroy'])
        ->name('admin.controlled-products.destroy');

    Route::post('/productos-controlados/{controlledProduct}/items', [ControlledProductReportController::class, 'storeItem'])
        ->name('admin.controlled-products.items.store');

    Route::delete('/productos-controlados/items/{item}', [ControlledProductReportController::class, 'destroyItem'])
        ->name('admin.controlled-products.items.destroy');

    Route::put('/productos-controlados/items/{item}', [ControlledProductReportController::class, 'updateItem'])
        ->name('admin.controlled-products.items.update');

    Route::put('/productos-controlados/{controlledProduct}/items', [ControlledProductReportController::class, 'updateItems'])
        ->name('admin.controlled-products.items.update-bulk');

    Route::get('/productos-controlados/{controlledProduct}/pdf', [ControlledProductReportController::class, 'exportPdf'])
        ->name('admin.controlled-products.pdf');
    Route::post('/recordatorios-productos', [CustomerProductRequestController::class, 'store'])
        ->name('admin.product-requests.store');
    Route::patch('/recordatorios-productos/{productRequest}/enviado', [CustomerProductRequestController::class, 'markAsSent'])
        ->name('admin.product-requests.mark-as-sent');
});