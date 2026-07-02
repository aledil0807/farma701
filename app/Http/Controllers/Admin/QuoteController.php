<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteGroup;
use App\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ExchangeRateService;

class QuoteController extends Controller
{
    public function index()
    {
        $quotes = Quote::query()
            ->latest()
            ->paginate(15);

        return view('admin.quotes.index', compact('quotes'));
    }

    public function store(Request $request, ExchangeRateService $exchangeRateService)
    {
        $data = $request->validate([
            'employee_name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $currentExchangeRate = (float) $exchangeRateService->getOfficialUsdToBsRate();
        } catch (\Throwable $e) {
            $currentExchangeRate = 1;
        }

        $quote = DB::transaction(function () use ($data, $currentExchangeRate) {
            $quote = Quote::create([
                'employee_name' => $data['employee_name'],
                'status' => 'draft',
                'exchange_rate' => $currentExchangeRate,
                'created_by' => null,
            ]);

            $quote->update([
                'quote_number' => 'P-' . str_pad($quote->id, 6, '0', STR_PAD_LEFT),
            ]);

            $quote->groups()->create([
                'name' => 'Conjunto 1',
                'position' => 1,
            ]);

            return $quote;
        });

        return redirect()
            ->route('admin.quotes.edit', $quote)
            ->with('success', 'Presupuesto creado correctamente.');
    }

    public function edit(Quote $quote)
    {
        if (!$quote->groups()->exists()) {
            $quote->groups()->create([
                'name' => 'Conjunto 1',
                'position' => 1,
            ]);
        }

        $quote->load([

            'groups.items.product',
        ]);

        return view('admin.quotes.edit', compact('quote'));
    }

    public function update(Request $request, Quote $quote)
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,sent,closed'],
            'exchange_rate' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $quote->update($data);

        $this->recalculateQuote($quote);

        return back()->with('success', 'Presupuesto actualizado correctamente.');
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();

        return redirect()
            ->route('admin.quotes.index')
            ->with('success', 'Presupuesto eliminado correctamente.');
    }

    public function storeGroup(Request $request, Quote $quote)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $nextPosition = ((int) $quote->groups()->max('position')) + 1;

        $quote->groups()->create([
            'name' => $data['name'],
            'position' => $nextPosition,
        ]);

        $this->recalculateQuote($quote);

        return back()->with('success', 'Conjunto agregado correctamente.');
    }

    public function updateGroup(Request $request, QuoteGroup $group)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $group->update($data);

        $this->recalculateQuote($group->quote);

        return back()->with('success', 'Conjunto actualizado correctamente.');
    }

    public function destroyGroup(QuoteGroup $group)
    {
        $quote = $group->quote;

        $group->delete();

        $this->recalculateQuote($quote);

        return back()->with('success', 'Conjunto eliminado correctamente.');
    }

    public function storeItem(Request $request, QuoteGroup $group)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::with('laboratory')->findOrFail($data['product_id']);

        $quote = $group->quote;
        $exchangeRate = (float) $quote->exchange_rate;

        $existingItem = $group->items()
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + (int) $data['quantity'],
            ]);

            $this->recalculateQuote($quote);

            if ($request->expectsJson()) {
                return $this->quoteAjaxResponse($quote, $group, 'Producto agregado al presupuesto.');
            }

            return back()->with('success', 'Cantidad del producto actualizada.');
        }

        $nextPosition = ((int) $group->items()->max('position')) + 1;

        $unitPriceUsd = (float) $product->price;
        $unitPriceBs = $unitPriceUsd * $exchangeRate;

        $group->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'laboratory_name' => $product->laboratory?->name,
            'quantity' => (int) $data['quantity'],
            'unit_price_usd' => $unitPriceUsd,
            'unit_price_bs' => $unitPriceBs,
            'subtotal_usd' => $unitPriceUsd * (int) $data['quantity'],
            'subtotal_bs' => $unitPriceBs * (int) $data['quantity'],
            'position' => $nextPosition,
        ]);

        $this->recalculateQuote($quote);

        if ($request->expectsJson()) {
            return $this->quoteAjaxResponse($quote, $group, 'Producto agregado al presupuesto.');
        }

        return back()->with('success', 'Producto agregado al presupuesto.');
    }

    public function updateItem(Request $request, QuoteItem $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price_usd' => ['required', 'numeric', 'min:0'],
        ]);

        $item->update([
            'quantity' => (int) $data['quantity'],
            'unit_price_usd' => (float) $data['unit_price_usd'],
        ]);

        $quote = $item->group->quote;
        $group = $item->group;

        $this->recalculateQuote($quote);

        if ($request->expectsJson()) {
            return $this->quoteAjaxResponse($quote, $group, 'Producto actualizado correctamente.');
        }

        return back()->with('success', 'Producto actualizado correctamente.');
    }

    public function destroyItem(Request $request, QuoteItem $item)
    {
        $group = $item->group;
        $quote = $group->quote;

        $item->delete();

        $this->recalculateQuote($quote);

        if ($request->expectsJson()) {
            return $this->quoteAjaxResponse($quote, $group, 'Producto eliminado del presupuesto.');
        }

        return back()->with('success', 'Producto eliminado del presupuesto.');
    }

    public function searchProducts(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $products = Product::query()
            ->where('is_active', true)
            ->select([
                'id',
                'name',
                'laboratory_id',
                'category_id',
                'cantidad',
                'price',
                'image_path',
            ])
            ->with([
                'laboratory:id,name',
                'category:id,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('laboratory', function ($labQuery) use ($search) {
                            $labQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'laboratory' => $product->laboratory?->name,
                    'category' => $product->category?->name,
                    'stock' => $product->cantidad,
                    'price' => number_format((float) $product->price, 2, '.', ''),
                    'image_url' => $product->image_url,
                ];
            });

        return response()->json([
            'products' => $products,
        ]);
    }

    private function recalculateQuote(Quote $quote): void
    {
        $quote->load('groups.items');

        $exchangeRate = (float) $quote->exchange_rate;

        $quoteTotalUsd = 0;
        $quoteTotalBs = 0;

        foreach ($quote->groups as $group) {
            $groupSubtotalUsd = 0;
            $groupSubtotalBs = 0;

            foreach ($group->items as $item) {
                $quantity = (int) $item->quantity;
                $unitPriceUsd = (float) $item->unit_price_usd;
                $unitPriceBs = $unitPriceUsd * $exchangeRate;

                $subtotalUsd = $unitPriceUsd * $quantity;
                $subtotalBs = $unitPriceBs * $quantity;

                $item->update([
                    'unit_price_bs' => $unitPriceBs,
                    'subtotal_usd' => $subtotalUsd,
                    'subtotal_bs' => $subtotalBs,
                ]);

                $groupSubtotalUsd += $subtotalUsd;
                $groupSubtotalBs += $subtotalBs;
            }

            $group->update([
                'subtotal_usd' => $groupSubtotalUsd,
                'subtotal_bs' => $groupSubtotalBs,
            ]);

            $quoteTotalUsd += $groupSubtotalUsd;
            $quoteTotalBs += $groupSubtotalBs;
        }

        $quote->update([
            'total_usd' => $quoteTotalUsd,
            'total_bs' => $quoteTotalBs,
        ]);
    }
    private function quoteAjaxResponse(Quote $quote, ?QuoteGroup $group = null, string $message = 'Actualizado correctamente.')
    {
        $quote->load('groups.items.product');

        $freshGroup = null;

        if ($group) {
            $freshGroup = QuoteGroup::with('items.product')->find($group->id);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'quote_total_html' => view('admin.quotes.partials.total-card', [
                'quote' => $quote,
            ])->render(),
            'group_id' => $freshGroup?->id,
            'group_html' => $freshGroup
                ? view('admin.quotes.partials.group-card', [
                    'group' => $freshGroup,
                ])->render()
                : null,
        ]);
    }
}