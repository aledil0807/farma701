<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Services\ExchangeRateService;

class ProductSearch extends Component
{
    public $search = '';
    public $perPage = 15;
    public $exchangeRate = 0;
    public $filter = '';
    public $showSearchHeader = false;

    public function mount(
        ExchangeRateService $exchangeRateService,
        $initialSearch = '',
        $showSearchHeader = false
    ) {
        $this->search = $initialSearch;
        $this->showSearchHeader = $showSearchHeader;

        try {
            $this->exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $this->exchangeRate = 0;
        }
    }

    public function updatingSearch()
    {
        $this->perPage = 15;
    }

    public function updatingFilter()
    {
        $this->perPage = 15;
    }

    public function loadMore()
    {
        $this->perPage += 15;
    }

    public function render()
    {
        $query = Product::with(['laboratory', 'category'])
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('laboratory', function ($labQuery) {
                        $labQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });

        if ($this->filter === 'name_asc') {
            $query->orderBy('name', 'asc');
        } elseif ($this->filter === 'name_desc') {
            $query->orderBy('name', 'desc');
        } else {
            $query->orderBy('id');
        }

        $totalProducts = (clone $query)->count();
        $products = $query->take($this->perPage)->get();

        return view('livewire.product-search', [
            'products' => $products,
            'hasMoreProducts' => $totalProducts > $this->perPage,
            'totalProducts' => $totalProducts,
            'exchangeRate' => $this->exchangeRate,
        ]);
    }
}