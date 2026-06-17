<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $selectedProducts = Product::query()
            ->select([
                'id',
                'name',
                'laboratory_id',
                'category_id',
                'cantidad',
                'price',
                'image_path',
                'is_monthly_product',
                'monthly_order',
            ])
            ->with([
                'laboratory:id,name',
                'category:id,name',
            ])
            ->where('is_monthly_product', true)
            ->orderByRaw('monthly_order IS NULL, monthly_order ASC')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->select([
                'id',
                'name',
                'laboratory_id',
                'category_id',
                'cantidad',
                'price',
                'image_path',
                'is_monthly_product',
                'monthly_order',
            ])
            ->with([
                'laboratory:id,name',
                'category:id,name',
            ])
            ->where('is_monthly_product', false)
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
            ->paginate(20)
            ->withQueryString();

        return view('admin.monthly-products.index', compact(
            'selectedProducts',
            'products',
            'search'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'monthly_products' => ['nullable', 'array'],
            'monthly_products.*' => ['integer', 'exists:products,id'],
            'monthly_order' => ['nullable', 'array'],
            'monthly_order.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $selectedProducts = collect($data['monthly_products'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($selectedProducts, $request) {
            Product::query()->update([
                'is_monthly_product' => false,
                'monthly_order' => null,
            ]);

            foreach ($selectedProducts as $index => $productId) {
                $customOrder = $request->input("monthly_order.$productId");

                Product::where('id', $productId)->update([
                    'is_monthly_product' => true,
                    'monthly_order' => $customOrder ?: ($index + 1),
                ]);
            }
        });

        return back()->with('success', 'Productos del mes actualizados correctamente.');
    }
    public function search(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $products = Product::query()
            ->select([
                'id',
                'name',
                'laboratory_id',
                'category_id',
                'cantidad',
                'price',
                'image_path',
                'is_monthly_product',
                'monthly_order',
            ])
            ->with([
                'laboratory:id,name',
                'category:id,name',
            ])
            ->where('is_monthly_product', false)
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
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'html' => view('admin.monthly-products.partials.product-results', compact('products'))->render(),
            'next_page_url' => $products->nextPageUrl(),
            'has_more' => $products->hasMorePages(),
        ]);
    }
}

