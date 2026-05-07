<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    // Muestra el formulario de subida
    public function showForm()
    {
        return view('admin.import');
    }

    // Procesa el archivo Excel
    public function import(Request $request)
    {
        // Validamos que el archivo sea obligatorio y sea un formato excel
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('file');
            
            // Llamamos a la clase de importación
            Excel::import(new ProductsImport, $file);

            return back()->with('success', '¡Catálogo actualizado correctamente!');
        } catch (\Exception $e) {
            return back()->with('error', 'Hubo un error al importar: ' . $e->getMessage());
        }
    }
}