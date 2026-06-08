<?php

namespace App\Http\Controllers;

use App\Imports\ProductsImport;
use App\Models\Category;
use App\Models\Laboratory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function showForm()
    {
        return view('admin.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        DB::beginTransaction();

        try {
            Product::query()->delete();
            Category::query()->delete();
            

            Excel::import(new ProductsImport, $request->file('file'));

            DB::commit();

            return back()->with('success', 'Catálogo reemplazado e importado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Error al importar el catálogo: ' . $e->getMessage());
        }
    }
}