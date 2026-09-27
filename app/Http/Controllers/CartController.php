<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\ExchangeRateService;
use App\Models\WebOrder;
use Illuminate\Support\Facades\DB;
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
            'attention_code' => ['nullable', 'string', 'max:100'],
            'delivery_type' => ['required', 'string'],
            'delivery_address' => ['nullable', 'string', 'max:500', 'required_if:delivery_type,delivery'],
            'payment_method' => ['required', 'string'],
        ], [
            'document.required' => 'Debes ingresar el documento.',
            'name.required' => 'Debes ingresar el nombre.',
            'phone.required' => 'Debes ingresar el teléfono.',
            'delivery_type.required' => 'Debes seleccionar el tipo de entrega.',
            'delivery_address.required_if' => 'Debes ingresar la dirección de envío cuando el pedido es por delivery.',
            'payment_method.required' => 'Debes seleccionar el método de pago.',
        ], [
            'document' => 'documento',
            'name' => 'nombre',
            'phone' => 'teléfono',
            'attention_code' => 'código de atención',
            'delivery_type' => 'tipo de entrega',
            'delivery_address' => 'dirección de envío',
            'payment_method' => 'método de pago',
        ]);

        if (
            ($data['delivery_type'] ?? null) === 'delivery' &&
            ($data['payment_method'] ?? null) === 'tarjeta'
        ) {
            return response()->json([
                'message' => 'No se puede seleccionar Delivery con pago por tarjeta de crédito/débito.',
                'errors' => [
                    'payment_method' => [
                        'No se puede seleccionar tarjeta de crédito/débito cuando el método de entrega es Delivery.',
                    ],
                ],
            ], 422);
        }

        $stockErrors = $cartService->validateStock();

        if (!empty($stockErrors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => implode("\n", $stockErrors),
                    'errors' => ['stock' => $stockErrors],
                ], 422);
            }

            return back()->with('stock_errors', $stockErrors)->withInput();
        }

        $cart = $cartService->getCart();
        $items = $cart['items'];

        if (empty($items)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu carrito está vacío.',
                    'errors' => ['cart' => ['Tu carrito está vacío.']],
                ], 422);
            }

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
            'cashea' => 'Cashea',
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
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No está configurado el número de WhatsApp de la empresa.',
                    'errors' => ['whatsapp' => ['No está configurado el número de WhatsApp de la empresa.']],
                ], 422);
            }

            return back()->with('stock_errors', ['No está configurado el número de WhatsApp de la empresa.'])->withInput();
        }

        $params = http_build_query([
            'phone' => $phoneNumber,
            'text' => $text,
        ], '', '&', PHP_QUERY_RFC3986);

        $whatsAppUrl = 'https://api.whatsapp.com/send?' . $params;

        $cartService->clear();

        $this->recordWebOrderFromCart($items, $cart, $totals);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'whatsapp_url' => $whatsAppUrl,
            ]);
        }
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
            'exchange_rate' => $exchangeRate,
        ]);
    }

    private function recordWebOrderFromCart(array $data, array $cart, array $totals): WebOrder
    {
        $items = collect($cart['items'] ?? $cart)
            ->filter(function ($item) {
                $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                return $productId > 0 && $quantity > 0;
            })
            ->values();

        $productIds = $items
            ->map(fn($item) => (int) ($item['product_id'] ?? $item['id']))
            ->unique()
            ->values();

        $products = Product::with('laboratory')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($data, $cart, $totals, $items, $products) {
            $webOrder = WebOrder::create([
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,

                'delivery_type' => $data['delivery_type'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,

                'payment_method' => $data['payment_method'] ?? null,

                'items_count' => (int) ($totals['items_count'] ?? $items->count()),
                'units_count' => (int) ($totals['units_count'] ?? $items->sum(fn($item) => (int) ($item['quantity'] ?? 0))),

                'subtotal_usd' => (float) ($totals['subtotal_usd'] ?? 0),
                'subtotal_bs' => (float) ($totals['subtotal_bs'] ?? 0),

                'discount_percent' => (float) ($totals['discount_percent'] ?? 0),
                'discount_usd' => (float) ($totals['discount_usd'] ?? 0),
                'discount_bs' => (float) ($totals['discount_bs'] ?? 0),

                'total_usd' => (float) ($totals['total_usd'] ?? 0),
                'total_bs' => (float) ($totals['total_bs'] ?? 0),

                'status' => 'sent_to_whatsapp',
                'ordered_at' => now(),
            ]);

            $exchangeRate = 0;

            if (
                isset($totals['subtotal_usd'], $totals['subtotal_bs']) &&
                (float) $totals['subtotal_usd'] > 0
            ) {
                $exchangeRate = (float) $totals['subtotal_bs'] / (float) $totals['subtotal_usd'];
            }

            $laboratories = [];

            foreach ($items as $item) {
                $productId = (int) ($item['product_id'] ?? $item['id']);
                $quantity = (int) ($item['quantity'] ?? 0);

                $product = $products->get($productId);

                $laboratoryId = $product?->laboratory_id;
                $laboratoryName = $product?->laboratory?->name ?: 'Sin laboratorio';

                $key = $laboratoryId ? 'lab_' . $laboratoryId : 'sin_laboratorio';

                $priceUsd = (float) ($item['price_usd'] ?? $item['price'] ?? 0);
                $lineUsd = $priceUsd * $quantity;
                $lineBs = $exchangeRate > 0 ? $lineUsd * $exchangeRate : 0;

                if (!isset($laboratories[$key])) {
                    $laboratories[$key] = [
                        'laboratory_id' => $laboratoryId,
                        'laboratory_name' => $laboratoryName,
                        'products_count' => 0,
                        'units_count' => 0,
                        'total_usd' => 0,
                        'total_bs' => 0,
                    ];
                }

                $laboratories[$key]['products_count']++;
                $laboratories[$key]['units_count'] += $quantity;
                $laboratories[$key]['total_usd'] += $lineUsd;
                $laboratories[$key]['total_bs'] += $lineBs;
            }

            foreach ($laboratories as $laboratory) {
                $webOrder->laboratoryMetrics()->create($laboratory);
            }

            return $webOrder;
        });
    }
}