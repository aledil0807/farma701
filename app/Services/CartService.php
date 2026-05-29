<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    protected string $sessionKey = 'cart';

    public function getCart(): array
    {
        $cart = session()->get($this->sessionKey, ['items' => []]);

        $cart = $this->syncCartWithDatabase($cart);

        session()->put($this->sessionKey, $cart);

        return $cart;
    }

    public function saveCart(array $cart): void
    {
        session()->put($this->sessionKey, $cart);
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->getCart();
        $items = $cart['items'];

        $currentQuantity = isset($items[$product->id])
            ? (int) $items[$product->id]['quantity']
            : 0;

        $newQuantity = min($currentQuantity + $quantity, (int) $product->cantidad);

        if ($newQuantity <= 0) {
            return;
        }

        $items[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price_usd' => (float) $product->price,
            'quantity' => $newQuantity,
            'image_url' => $product->image_url,
            'laboratory' => $product->laboratory?->name ?? 'NO DEFINIDO',
            'stock' => (int) $product->cantidad,
            'is_controlled' => (bool) $product->is_controlled,
        ];

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

    public function getProductQuantity(int $productId): int
    {
        $cart = $this->getCart();

        return isset($cart['items'][$productId])
            ? (int) $cart['items'][$productId]['quantity']
            : 0;
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

    public function totals(float $exchangeRate = 0): array
    {
        $cart = $this->getCart();
        $items = $cart['items'];

        $subtotalUsd = 0;

        foreach ($items as $item) {
            $subtotalUsd += $item['price_usd'] * $item['quantity'];
        }

        $discountPercent = 5;
        $discountUsd = $subtotalUsd * ($discountPercent / 100);
        $totalUsd = $subtotalUsd - $discountUsd;

        $subtotalBs = $exchangeRate > 0 ? $subtotalUsd * $exchangeRate : 0;
        $discountBs = $exchangeRate > 0 ? $discountUsd * $exchangeRate : 0;
        $totalBs = $exchangeRate > 0 ? $totalUsd * $exchangeRate : 0;

        return [
            'subtotal_usd' => $subtotalUsd,
            'subtotal_bs' => $subtotalBs,
            'discount_percent' => $discountPercent,
            'discount_usd' => $discountUsd,
            'discount_bs' => $discountBs,
            'total_usd' => $totalUsd,
            'total_bs' => $totalBs,
            'items_count' => count($items),
            'units_count' => array_sum(array_column($items, 'quantity')),
        ];
    }

    protected function syncCartWithDatabase(array $cart): array
    {
        $items = $cart['items'] ?? [];

        if (empty($items)) {
            return ['items' => []];
        }

        $productIds = array_keys($items);

        $products = Product::with('laboratory')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $productId => $item) {
            $product = $products->get($productId);

            // Si ya no existe, lo sacamos del carrito
            if (!$product) {
                unset($items[$productId]);
                continue;
            }

            // Refrescamos los datos por si cambiaron en el Excel
            $items[$productId]['name'] = $product->name;
            $items[$productId]['price_usd'] = (float) $product->price;
            $items[$productId]['image_url'] = $product->image_url;
            $items[$productId]['laboratory'] = $product->laboratory?->name ?? 'NO DEFINIDO';
            $items[$productId]['stock'] = (int) $product->cantidad;
            $items[$productId]['is_controlled'] = (bool) $product->is_controlled;
            // Opcional: si quieres capar cantidad al stock actual
            if ((int) $items[$productId]['quantity'] > (int) $product->cantidad) {
                $items[$productId]['quantity'] = max(1, (int) $product->cantidad);
            }

            // Si el stock quedó en 0, puedes decidir si lo dejas o lo sacas.
            // Si quieres sacarlo automáticamente, descomenta esto:
            // if ((int) $product->cantidad <= 0) {
            //     unset($items[$productId]);
            // }
        }

        return ['items' => $items];
    }
}