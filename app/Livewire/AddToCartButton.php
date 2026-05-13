<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Services\CartService;

class AddToCartButton extends Component
{
    public Product $product;
    public int $quantity = 0;

    public function mount(Product $product, CartService $cartService)
    {
        $this->product = $product;
        $this->quantity = $cartService->getProductQuantity($product->id);
    }

    public function add(CartService $cartService)
    {
        $this->quantity++; // optimista

        try {
            $cartService->add($this->product, 1);
            $this->quantity = $cartService->getProductQuantity($this->product->id);
            $this->dispatch('cart-updated');
        } catch (\Throwable $e) {
            $this->quantity = max(0, $this->quantity - 1);
        }
    }

    public function increment(CartService $cartService)
    {
        $this->quantity++; // optimista

        try {
            $cartService->increment($this->product->id);
            $this->quantity = $cartService->getProductQuantity($this->product->id);
            $this->dispatch('cart-updated');
        } catch (\Throwable $e) {
            $this->quantity = max(0, $this->quantity - 1);
        }
    }

    public function decrement(CartService $cartService)
    {
        if ($this->quantity <= 0) {
            return;
        }

        $this->quantity--; // optimista

        try {
            $cartService->decrement($this->product->id);
            $this->quantity = $cartService->getProductQuantity($this->product->id);
            $this->dispatch('cart-updated');
        } catch (\Throwable $e) {
            $this->quantity++;
        }
    }

    public function render()
    {
        return view('livewire.add-to-cart-button');
    }
}