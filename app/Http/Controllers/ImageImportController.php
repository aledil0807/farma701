<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

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
            'images.*' => ['required', 'image', 'max:5120'],
        ], [
            'images.required' => 'Debes seleccionar al menos una imagen.',
            'images.array' => 'El formato de carga de imágenes no es válido.',
            'images.min' => 'Debes seleccionar al menos una imagen.',
            'images.*.image' => 'Uno de los archivos seleccionados no es una imagen válida.',
            'images.*.max' => 'Cada imagen debe pesar máximo 5 MB.',
        ]);

        $files = $request->file('images', []);

        $errors = [];
        $updated = 0;

        foreach ($files as $file) {
            $name = trim($file->getClientOriginalName());

            $product = Product::where('image_path', $name)->first();

            if (! $product) {
                $errors[] = $name;
                continue;
            }

            
            $file->storeAs('products', $name, 'public');

            $updated++;
        }

        $payload = [
            'message' => "Se actualizaron {$updated} imágenes.",
            'received' => count($files),
            'updated' => $updated,
            'unmatched_count' => count($errors),
            'unmatched_images' => $errors,
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        return back()->with([
            'message' => "Se actualizaron {$updated} imágenes.",
            'unmatched_images' => $errors,
        ]);
    }
}