<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ImageImportController extends Controller
{
    public function show()
    {
        return view('admin.products.import-images');
    }

    public function import(Request $request)
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', /*'mimes:jpeg,png,jpg,webp',*/ 'max:5120'],
        ], [
            'images.required' => 'Debes seleccionar al menos una imagen.',
            'images.array' => 'El formato de carga de imágenes no es válido.',
            'images.min' => 'Debes seleccionar al menos una imagen.',
            'images.*.image' => 'Uno de los archivos seleccionados no es una imagen válida.',
            //'images.*.mimes' => 'Solo se permiten imágenes JPG, JPEG, PNG y WEBP.',
            'images.*.max' => 'Cada imagen debe pesar máximo 5 MB.',
        ]);

        $files = $request->file('images');

        $errors = [];
        $updated = 0;

        foreach ($files as $file) {
            $name = $file->getClientOriginalName();

            $product = Product::where('image_path', $name)->first();

            if (!$product) {
                $errors[] = $name;
                continue;
            }

            $file->storeAs('products', $name, 'public');

            $updated++;
        }

        return back()->with([
            'message' => "Se actualizaron $updated imágenes",
            'unmatched_images' => $errors,
        ]);
    }
}