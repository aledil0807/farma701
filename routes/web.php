<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ImageImportController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AdminAuthController;


// Ruta para ver el formulario
Route::get('/importar', [ImportController::class, 'showForm'])->name('import.form');

// Ruta para procesar la subida
Route::post('/importar', [ImportController::class, 'import'])->name('import.process');

//Ruta para ver el catálogo
Route::get('/', function () {
    return view('inicio');
})->name('home');

Route::get('/busqueda', function () {
    return view('busqueda');
})->name('search.results');






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

    Route::get('/productos/crear', [ProductController::class, 'create'])->name('products.create');
    Route::post('/productos/guardar', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/import-images', [ImageImportController::class, 'show'])->name('admin.products.import-images.show');
    Route::post('/products/import-images', [ImageImportController::class, 'import'])->name('admin.products.import-images');

    Route::get('/importar', [ImportController::class, 'showForm'])->name('import.form');
    Route::post('/importar', [ImportController::class, 'import'])->name('import.process');
});