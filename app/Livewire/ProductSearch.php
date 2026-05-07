<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use Livewire\WithPagination;

class ProductSearch extends Component
{
    use WithPagination;

    // Esta variable está vinculada al input de búsqueda
    public $search = '';

    // Resetea la página a la 1 cada vez que el usuario escribe algo
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Buscamos productos que coincidan con el nombre 
        // O que el laboratorio asociado coincida con la búsqueda
        $products = Product::where('name', 'like', '%' . $this->search . '%')
            ->orWhereHas('laboratory', function($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->paginate(12);

        return view('livewire.product-search', [
            'products' => $products
        ]);
    }
}