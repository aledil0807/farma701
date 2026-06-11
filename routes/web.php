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
    $featuredProductIds = [101, 205, 330, 411, 522];

    $featuredProducts = Product::with(['category', 'laboratory'])
        ->whereIn('id', $featuredProductIds)
        ->get()
        ->sortBy(function ($product) use ($featuredProductIds) {
            return array_search($product->id, $featuredProductIds);
        })
        ->values();

    $allyLabs = Laboratory::whereIn('name', $featuredLabs)->get()
        ->sortBy(function ($lab) use ($featuredLabs) {
            return array_search($lab->name, $featuredLabs);
        })
        ->values();

    return view('inicio', compact('banners', 'allyLabs','featuredProducts'));
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
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

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
});