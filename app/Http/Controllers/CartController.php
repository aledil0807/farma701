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
            'delivery_address' => ['nullable', 'string', 'max:500', 'required_if:delivery_type,delivery'],
            'payment_method' => ['required', 'string'],
            'attention_code' => ['nullable', 'string', 'max:100'],
        ], [
            'delivery_address.required_if' => 'Debes ingresar la dirección de envío cuando el pedido es por delivery.',
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
            'tarjeta' => 'Tarjeta de crédito/débito',
        ];

        $message = [];
        $message[] = '💊 Farmacia 701 - Nuevo Pedido:';
        $message[] = '';
        $message[] = 'DATOS DEL CLIENTE';
        $message[] = '👤 Cliente: ' . $request->name;
        $message[] = '📄 Documento: ' . $request->document;
        $message[] = '📱 Teléfono: ' . $request->phone;
        if ($request->filled('attention_code')) {
            $message[] = 'Código Atención: ' . $request->attention_code;
        }
        $message[] = '🚚 Entrega: ' . ($deliveryLabels[$request->delivery_type] ?? $request->delivery_type);
        if ($request->delivery_type === 'delivery' && $request->filled('delivery_address')) {
            $message[] = 'Dirección de envío: ' . $request->delivery_address;
        }
        $message[] = '💳 Método Pago: ' . ($paymentLabels[$request->payment_method] ?? $request->payment_method);
        $message[] = '';
        $message[] = '🛒 Productos Solicitados:';

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

        $message[] = 'SUBTOTAL';
        $message[] = 'Bs. ' . number_format($totals['subtotal_bs'], 2, ',', '.') . ' | $ ' . number_format($totals['subtotal_usd'], 2, '.', ',');
        $message[] = 'DESCUENTO (' . $totals['discount_percent'] . '%)';
        $message[] = 'Bs. ' . number_format($totals['discount_bs'], 2, ',', '.') . ' | $ ' . number_format($totals['discount_usd'], 2, '.', ',');
        $message[] = '';
        $message[] = 'TOTAL A CANCELAR';
        $message[] = 'Bs. ' . number_format($totals['total_bs'], 2, ',', '.') . ' | $ ' . number_format($totals['total_usd'], 2, '.', ',');

        $text = implode("\n", $message);

        $phoneNumber = config('services.whatsapp.number');

        if (!$phoneNumber) {
            return back()->with('stock_errors', ['No está configurado el número de WhatsApp de la empresa.'])->withInput();
        }

        $params = http_build_query([
            'phone' => $phoneNumber,
            'text' => $text,
        ], '', '&', PHP_QUERY_RFC3986);

        $whatsAppUrl = 'https://api.whatsapp.com/send?' . $params;
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