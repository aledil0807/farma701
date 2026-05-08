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
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $files = $request->file('images');

        $errors = [];
        $updated = 0;

        foreach ($files as $file) {
            $name = $file->getClientOriginalName();

            // Buscamos el producto por un identificador (puede ser id)
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