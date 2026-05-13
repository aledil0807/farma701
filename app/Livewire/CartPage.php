<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CartService;
use App\Services\ExchangeRateService;

class CartPage extends Component
{
    public array $cartItems = [];
    public array $totals = [];
    public float $exchangeRate = 0;

    public function mount(CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        try {
            $this->exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $this->exchangeRate = 0;
        }

        $this->refreshCart($cartService);
    }

    public function increment(int $productId, CartService $cartService)
    {
        $cartService->increment($productId);
        $this->refreshCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function decrement(int $productId, CartService $cartService)
    {
        $cartService->decrement($productId);
        $this->refreshCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function remove(int $productId, CartService $cartService)
    {
        $cartService->remove($productId);
        $this->refreshCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function clear(CartService $cartService)
    {
        $cartService->clear();
        $this->refreshCart($cartService);
        $this->dispatch('cart-updated');
    }

    protected function refreshCart(CartService $cartService): void
    {
        $cart = $cartService->getCart();
        $this->cartItems = $cart['items'];
        $this->totals = $cartService->totals($this->exchangeRate);
    }

    public function render()
    {
        return view('livewire.cart-page');
    }
}