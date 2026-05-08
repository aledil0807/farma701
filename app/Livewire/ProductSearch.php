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

    public function mount(ExchangeRateService $exchangeRateService)
    {
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

    public function loadMore()
    {
        $this->perPage += 15;
    }

    public function render()
    {
        $query = Product::with(['laboratory'])
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('laboratory', function ($labQuery) {
                        $labQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy('id');

        $totalProducts = (clone $query)->count();
        $products = $query->take($this->perPage)->get();

        return view('livewire.product-search', [
            'products' => $products,
            'hasMoreProducts' => $totalProducts > $this->perPage,
            'exchangeRate' => $this->exchangeRate,
        ]);
    }
}