<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CartService;

class CartBadge extends Component
{
    public int $unitsCount = 0;

    protected $listeners = ['cart-updated' => 'refreshBadge'];

    public function mount(CartService $cartService)
    {
        $this->refreshBadge($cartService);
    }

    public function refreshBadge(CartService $cartService)
    {
        $totals = $cartService->totals();
        $this->unitsCount = $totals['units_count'] ?? 0;
    }

    public function render()
    {
        return view('livewire.cart-badge');
    }
}