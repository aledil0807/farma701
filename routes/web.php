<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ImageImportController;

// Ruta para ver el formulario
Route::get('/importar', [ImportController::class, 'showForm'])->name('import.form');

// Ruta para procesar la subida
Route::post('/importar', [ImportController::class, 'import'])->name('import.process');

//Ruta para ver el catálogo
Route::get('/', function () {
    return view('inicio');
});



Route::get('/admin/products/import-images', [ImageImportController::class, 'show'])->name('admin.products.import-images.show');
Route::post('/admin/products/import-images', [ImageImportController::class, 'import'])->name('admin.products.import-images');



Route::get('/admin/productos/crear', [ProductController::class, 'create'])->name('products.create');
Route::post('/admin/productos/guardar', [ProductController::class, 'store'])->name('products.store');