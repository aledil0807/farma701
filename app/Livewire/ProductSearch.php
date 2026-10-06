<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Services\ExchangeRateService;

class ProductSearch extends Component
{
    public $search = '';
    public $perPage = 16;
    public $exchangeRate = 0;
    public $filter = '';
    public $showSearchHeader = false;
    public $laboratoryId = null;
    public ?string $customTitle = null;

    public string $mode = 'catalog';
    public string $layout = 'catalog';

    public function mount(
        ExchangeRateService $exchangeRateService,
        $initialSearch = '',
        $showSearchHeader = false,
        ?int $laboratoryId = null,
        ?string $customTitle = null
    ) {
        $this->search = $initialSearch;
        $this->showSearchHeader = $showSearchHeader;
        $this->laboratoryId = $laboratoryId;
        $this->customTitle = $customTitle;

        try {
            $this->exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $this->exchangeRate = 0;
        }
    }

    public function updatingSearch()
    {
        $this->perPage = 16;
    }

    public function updatingFilter()
    {
        $this->perPage = 16;
    }

    public function loadMore()
    {
        $this->perPage += 16;
    }

    public function render()
    {
        $query = Product::with(['laboratory', 'category'])
            ->where('is_active', true)
            ->where('is_public', true);

        if ($this->mode === 'monthly') {
            $query->where('is_monthly_product', true);
        } else {
            if ($this->laboratoryId) {
                $query->where('laboratory_id', $this->laboratoryId);
            }

            if (trim($this->search) !== '') {
                $query->searchByTerms($this->search);
            }
        }

        if ($this->mode === 'monthly') {
            $query->orderByRaw('monthly_order IS NULL, monthly_order ASC')
                ->orderBy('monthly_order')
                ->orderBy('name');
        } else {
            switch ($this->filter) {
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;

                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;

                case 'price_asc':
                    $query->orderBy('price', 'asc')
                        ->orderBy('name', 'asc');
                    break;

                case 'price_desc':
                    $query->orderBy('price', 'desc')
                        ->orderBy('name', 'asc');
                    break;

                case 'laboratory_asc':
                    $query->orderByRaw('(SELECT name FROM laboratories WHERE laboratories.id = products.laboratory_id) IS NULL ASC')
                        ->orderByRaw('(SELECT name FROM laboratories WHERE laboratories.id = products.laboratory_id) ASC')
                        ->orderBy('name', 'asc');
                    break;

                default:
                    $query->orderBy('id', 'asc');
                    break;
            }
        }

        $totalProducts = (clone $query)->count();

        $products = $query
            ->take($this->perPage)
            ->get();

        return view('livewire.product-search', [
            'products' => $products,
            'hasMoreProducts' => $totalProducts > $this->perPage,
            'totalProducts' => $totalProducts,
            'exchangeRate' => $this->exchangeRate,
        ]);
    }
}