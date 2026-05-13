<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    protected string $sessionKey = 'cart';

    public function getCart(): array
    {
        return session()->get($this->sessionKey, ['items' => []]);
    }

    public function saveCart(array $cart): void
    {
        session()->put($this->sessionKey, $cart);
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->getCart();
        $items = $cart['items'];

        if (isset($items[$product->id])) {
            $items[$product->id]['quantity'] += $quantity;
        } else {
            $items[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price_usd' => (float) $product->price,
                'quantity' => $quantity,
                'image_url' => $product->image_url,
                'laboratory' => $product->laboratory?->name ?? 'NO DEFINIDO',
            ];
        }

        $cart['items'] = $items;
        $this->saveCart($cart);
    }

    public function increment(int $productId): void
    {
        $cart = $this->getCart();

        if (isset($cart['items'][$productId])) {
            $cart['items'][$productId]['quantity']++;
            $this->saveCart($cart);
        }
    }

    public function decrement(int $productId): void
    {
        $cart = $this->getCart();

        if (!isset($cart['items'][$productId])) {
            return;
        }

        $cart['items'][$productId]['quantity']--;

        if ($cart['items'][$productId]['quantity'] <= 0) {
            unset($cart['items'][$productId]);
        }

        $this->saveCart($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->getCart();

        if (isset($cart['items'][$productId])) {
            unset($cart['items'][$productId]);
            $this->saveCart($cart);
        }
    }

    public function clear(): void
    {
        session()->forget($this->sessionKey);
    }

    public function totals(float $exchangeRate = 0): array
    {
        $cart = $this->getCart();
        $items = $cart['items'];

        $subtotalUsd = 0;

        foreach ($items as $item) {
            $subtotalUsd += $item['price_usd'] * $item['quantity'];
        }

        $subtotalBs = $exchangeRate > 0 ? $subtotalUsd * $exchangeRate : 0;

        return [
            'subtotal_usd' => $subtotalUsd,
            'subtotal_bs' => $subtotalBs,
            'items_count' => count($items),
            'units_count' => array_sum(array_column($items, 'quantity')),
        ];
    }
    public function validateStock(): array
    {
        $cart = $this->getCart();
        $items = $cart['items'];
        $errors = [];

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);

            if (!$product) {
                $errors[] = "El producto {$item['name']} ya no existe.";
                continue;
            }

            if ((int) $item['quantity'] > (int) $product->cantidad) {
                $errors[] = "Stock insuficiente para {$product->name}. Disponible: {$product->cantidad}, solicitado: {$item['quantity']}.";
            }
        }

        return $errors;
    }
    public function getProductQuantity(int $productId): int
    {
        $cart = $this->getCart();

        return isset($cart['items'][$productId])
            ? (int) $cart['items'][$productId]['quantity']
            : 0;
    }
}