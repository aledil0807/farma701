<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Laboratory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function create()
    {
        $categories = Category::all();
        $laboratories = Laboratory::all();
        return view('admin.products.create', compact('categories', 'laboratories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|unique:products,id', // El SKU que usamos como ID
            'name' => 'required',
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB
        ]);

        $data = $request->all();

        // Manejo de la imagen
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = $file->getClientOriginalName(); // Ejemplo: "aspirina.jpg"

            // Guardamos el archivo físicamente en 'public/products'
            $file->storeAs('products', $name, 'public');

            // Guardamos en la base de datos con el prefijo incluido: "products/aspirina.jpg"
            $data['image_path'] = $name;
        }

        // Convertir checkbox a booleano
        $data['has_iva'] = $request->has('has_iva');
        $data['is_controlled'] = $request->has('is_controlled');

        Product::create($data);

        return redirect()->route('admin.dashboard')->with('success', 'Producto creado manualmente con éxito.');
    }
}