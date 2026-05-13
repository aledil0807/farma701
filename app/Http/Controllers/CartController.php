<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        $exchangeRate = 0;

        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        $cart = $cartService->getCart();
        $totals = $cartService->totals($exchangeRate);

        return view('carrito', [
            'cartItems' => $cart['items'],
            'totals' => $totals,
            'exchangeRate' => $exchangeRate,
        ]);
    }

    public function add(Request $request, Product $product, CartService $cartService)
    {
        $quantity = max(1, (int) $request->input('quantity', 1));
        $cartService->add($product, $quantity);

        return back()->with('success', 'Producto agregado al carrito.');
    }

    public function increment(Product $product, CartService $cartService)
    {
        $cartService->increment($product->id);
        return back();
    }

    public function decrement(Product $product, CartService $cartService)
    {
        $cartService->decrement($product->id);
        return back();
    }

    public function remove(Product $product, CartService $cartService)
    {
        $cartService->remove($product->id);
        return back();
    }

    public function clear(CartService $cartService)
    {
        $cartService->clear();
        return back();
    }
    public function checkout(Request $request, CartService $cartService)
    {
        $request->validate([
            'document' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'delivery_type' => ['required', 'string'],
            'payment_method' => ['required', 'string'],
        ]);

        $stockErrors = $cartService->validateStock();

        if (!empty($stockErrors)) {
            return back()->with('stock_errors', $stockErrors)->withInput();
        }

        $cart = $cartService->getCart();
        $items = $cart['items'];

        if (empty($items)) {
            return back()->with('stock_errors', ['Tu carrito está vacío.'])->withInput();
        }

        $exchangeRate = 0;

        try {
            $exchangeRate = app(ExchangeRateService::class)->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        $totals = $cartService->totals($exchangeRate);

        $deliveryLabels = [
            'pickup' => 'Retiro en tienda',
            'delivery' => 'Delivery',
        ];

        $paymentLabels = [
            'pago_movil' => 'Pago móvil',
            'transferencia' => 'Transferencia',
            'efectivo_usd' => 'Efectivo USD',
            'efectivo_bs' => 'Efectivo Bs',
        ];

        $message = [];
        $message[] = 'Hola, quiero procesar la siguiente compra en Farmacia 701:';
        $message[] = '';
        $message[] = 'DATOS DEL CLIENTE';
        $message[] = 'Cédula o RIF: ' . $request->document;
        $message[] = 'Nombre / Empresa: ' . $request->name;
        $message[] = 'Teléfono: ' . $request->phone;
        $message[] = 'Tipo de entrega: ' . ($deliveryLabels[$request->delivery_type] ?? $request->delivery_type);
        $message[] = 'Método de pago: ' . ($paymentLabels[$request->payment_method] ?? $request->payment_method);
        $message[] = '';
        $message[] = 'PRODUCTOS';

        foreach ($items as $item) {
            $lineUsd = $item['price_usd'] * $item['quantity'];
            $lineBs = $exchangeRate > 0 ? $lineUsd * $exchangeRate : 0;

            $message[] = '- ' . $item['name'];
            $message[] = '  Laboratorio: ' . $item['laboratory'];
            $message[] = '  Cantidad: ' . $item['quantity'];
            $message[] = '  Total línea: Bs. ' . number_format($lineBs, 2, ',', '.') . ' | $ ' . number_format($lineUsd, 2, '.', ',');
            $message[] = '';
        }

        $message[] = 'TASA DEL DÍA';
        $message[] = 'Bs. ' . number_format($exchangeRate, 2, ',', '.');
        $message[] = '';
        $message[] = 'TOTAL A CANCELAR';
        $message[] = 'Bs. ' . number_format($totals['subtotal_bs'], 2, ',', '.') . ' | $ ' . number_format($totals['subtotal_usd'], 2, '.', ',');

        $text = implode("\n", $message);

        $phoneNumber = config('services.whatsapp.number');

        if (!$phoneNumber) {
            return back()->with('stock_errors', ['No está configurado el número de WhatsApp de la empresa.'])->withInput();
        }

        $whatsAppUrl = 'https://wa.me/' . $phoneNumber . '?text=' . urlencode($text);

        return redirect()->away($whatsAppUrl);
    }

    public function ajaxAdd(Request $request, Product $product, CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        $cartService->add($product, 1);

        $exchangeRate = 0;
        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        return response()->json([
            'success' => true,
            'quantity' => $cartService->getProductQuantity($product->id),
            'cart' => $cartService->getCart(),
            'totals' => $cartService->totals($exchangeRate),
        ]);
    }

    public function ajaxIncrement(Product $product, CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        $cartService->increment($product->id);

        $exchangeRate = 0;
        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        return response()->json([
            'success' => true,
            'quantity' => $cartService->getProductQuantity($product->id),
            'cart' => $cartService->getCart(),
            'totals' => $cartService->totals($exchangeRate),
        ]);
    }

    public function ajaxDecrement(Product $product, CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        $cartService->decrement($product->id);

        $exchangeRate = 0;
        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        return response()->json([
            'success' => true,
            'quantity' => $cartService->getProductQuantity($product->id),
            'cart' => $cartService->getCart(),
            'totals' => $cartService->totals($exchangeRate),
        ]);
    }

    public function ajaxRemove(Product $product, CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        $cartService->remove($product->id);

        $exchangeRate = 0;
        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        return response()->json([
            'success' => true,
            'cart' => $cartService->getCart(),
            'totals' => $cartService->totals($exchangeRate),
        ]);
    }

    public function ajaxClear(CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        $cartService->clear();

        $exchangeRate = 0;
        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        return response()->json([
            'success' => true,
            'cart' => $cartService->getCart(),
            'totals' => $cartService->totals($exchangeRate),
        ]);
    }

    public function ajaxSummary(CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        $exchangeRate = 0;
        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        return response()->json([
            'success' => true,
            'totals' => $cartService->totals($exchangeRate),
        ]);
    }

    public function ajaxDetail(CartService $cartService, ExchangeRateService $exchangeRateService)
    {
        $exchangeRate = 0;
        try {
            $exchangeRate = $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Exception $e) {
            $exchangeRate = 0;
        }

        return response()->json([
            'success' => true,
            'cart' => $cartService->getCart(),
            'totals' => $cartService->totals($exchangeRate),
        ]);
    }
}