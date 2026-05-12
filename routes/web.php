<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ImageImportController;
use App\Http\Controllers\CartController;


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



Route::get('/admin/products/import-images', [ImageImportController::class, 'show'])->name('admin.products.import-images.show');
Route::post('/admin/products/import-images', [ImageImportController::class, 'import'])->name('admin.products.import-images');



Route::get('/admin/productos/crear', [ProductController::class, 'create'])->name('products.create');
Route::post('/admin/productos/guardar', [ProductController::class, 'store'])->name('products.store');

Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/agregar/{product}', [CartController::class, 'add'])->name('cart.add');
Route::post('/carrito/incrementar/{product}', [CartController::class, 'increment'])->name('cart.increment');
Route::post('/carrito/disminuir/{product}', [CartController::class, 'decrement'])->name('cart.decrement');
Route::post('/carrito/eliminar/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/carrito/vaciar', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/carrito/procesar', [CartController::class, 'checkout'])->name('cart.checkout');