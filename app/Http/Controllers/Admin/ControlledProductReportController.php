<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ControlledProductReport;
use App\Models\ControlledProductReportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ControlledProductReportController extends Controller
{
    public function index()
    {
        $reports = ControlledProductReport::query()
            ->withCount('items')
            ->latest()
            ->get();

        $monthlyReports = $reports
            ->groupBy('report_month')
            ->map(function ($monthReports, $month) {
                return (object) [
                    'month' => $month,
                    'reports_count' => $monthReports->count(),
                    'items_count' => $monthReports->sum('items_count'),
                    'created_at' => $monthReports->sortByDesc('created_at')->first()?->created_at,
                    'categories' => $monthReports->pluck('category')->filter()->values(),
                ];
            })
            ->sortByDesc('month')
            ->values();

        return view('admin.controlled-products.index', compact('monthlyReports'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'report_month' => ['required', 'string', 'max:7'],
            'category' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $category = trim($data['category']);

        $previousReport = ControlledProductReport::query()
            ->with('items')
            ->whereRaw('LOWER(TRIM(category)) = ?', [mb_strtolower($category)])
            ->orderByDesc('report_month')
            ->orderByDesc('id')
            ->first();

        $report = DB::transaction(function () use ($data, $category, $previousReport) {
            $report = ControlledProductReport::create([
                'title' => $data['title'] ?: 'Reporte de productos controlados',
                'report_month' => $data['report_month'],
                'category' => $category,
                'notes' => $data['notes'] ?? null,
            ]);

            $report->update([
                'report_number' => 'PC-' . str_pad($report->id, 6, '0', STR_PAD_LEFT),
            ]);

            if ($previousReport) {
                foreach ($previousReport->items as $previousItem) {
                    $previousStock = (int) $previousItem->current_stock;

                    $report->items()->create([
                        'product_name' => $previousItem->product_name,
                        'drugstore' => null,
                        'invoice_number' => null,
                        'units_per_box' => (int) ($previousItem->units_per_box ?: 1),
                        'boxes_received' => 0,
                        'pills_received' => 0,
                        'previous_stock' => $previousStock,
                        'entries' => 0,
                        'exits' => 0,
                        'current_stock' => $previousStock,
                    ]);
                }
            }

            return $report;
        });

        return redirect()
            ->route('admin.controlled-products.show', $report)
            ->with(
                'success',
                $previousReport
                ? 'Reporte creado correctamente. Se importaron los productos del reporte anterior de la misma categoría.'
                : 'Reporte creado correctamente. No había un reporte anterior de esta categoría para importar.'
            );
    }

    public function show(ControlledProductReport $controlledProduct)
    {
        $controlledProduct->load([
            'items' => function ($query) {
                $query->orderBy('product_name');
            },
        ]);

        $currentItemsByProductName = $controlledProduct->items
            ->keyBy(function ($item) {
                return mb_strtolower(trim((string) $item->product_name));
            });

        $configuredProducts = collect($this->configuredProductsForCategory($controlledProduct->category))
            ->map(function ($product) use ($controlledProduct, $currentItemsByProductName) {
                $productName = trim((string) ($product['name'] ?? ''));
                $normalizedProductName = mb_strtolower($productName);

                $currentItem = $currentItemsByProductName->get($normalizedProductName);

                if ($currentItem) {
                    $product['previous_stock'] = (int) $currentItem->previous_stock;
                    $product['existing_entries'] = (int) $currentItem->entries;
                    $product['existing_exits'] = (int) $currentItem->exits;
                    $product['existing_current_stock'] = (int) $currentItem->current_stock;
                } else {
                    $previousStock = $this->previousStockForProduct(
                        $controlledProduct,
                        $productName
                    );

                    $product['previous_stock'] = $previousStock;
                    $product['existing_entries'] = 0;
                    $product['existing_exits'] = 0;
                    $product['existing_current_stock'] = $previousStock;
                }

                return $product;
            })
            ->values()
            ->all();

        return view('admin.controlled-products.show', [
            'report' => $controlledProduct,
            'configuredProducts' => $configuredProducts,
        ]);
    }

    public function destroy(ControlledProductReport $controlledProduct)
    {
        $controlledProduct->delete();

        return redirect()
            ->route('admin.controlled-products.index')
            ->with('success', 'Reporte eliminado correctamente.');
    }

    public function storeItem(Request $request, ControlledProductReport $controlledProduct)
    {
        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'drugstore' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'units_per_box' => ['required', 'integer', 'min:1'],
            'boxes_received' => ['nullable', 'integer', 'min:0'],
            'previous_stock' => ['required', 'integer', 'min:0'],
            'exits' => ['required', 'integer', 'min:0'],
        ]);

        $unitsPerBox = (int) $data['units_per_box'];
        $boxesReceived = (int) ($data['boxes_received'] ?? 0);
        $previousStock = (int) $data['previous_stock'];
        $entries = $unitsPerBox * $boxesReceived;
        $exits = (int) $data['exits'];

        $controlledProduct->items()->create([
            'product_name' => $data['product_name'],
            'drugstore' => $data['drugstore'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'units_per_box' => $unitsPerBox,
            'boxes_received' => $boxesReceived,
            'pills_received' => 0,
            'previous_stock' => $previousStock,
            'entries' => $entries,
            'exits' => $exits,
            'current_stock' => ControlledProductReportItem::calculateCurrentStock(
                $previousStock,
                $entries,
                $exits
            ),
        ]);

        return redirect()
            ->route('admin.controlled-products.show', $controlledProduct)
            ->with('success', 'Producto agregado al reporte correctamente.');
    }

    public function storeConfiguredItems(Request $request, ControlledProductReport $controlledProduct)
    {
        $configuredProducts = collect(
            $this->configuredProductsForCategory($controlledProduct->category)
        )->keyBy('code');

        $data = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'drugstore' => ['nullable', 'string', 'max:255'],

            'selected_products' => ['required', 'array', 'min:1'],
            'selected_products.*' => ['required', 'string'],

            'products' => ['nullable', 'array'],
            'products.*.previous_stock' => ['nullable', 'regex:/^\d+$/'],
            'products.*.units_per_box' => ['nullable', 'regex:/^\d+$/'],
            'products.*.boxes_received' => ['nullable', 'regex:/^\d+$/'],
            'products.*.exits' => ['nullable', 'regex:/^\d+$/'],
        ], [
            'selected_products.required' => 'Debes seleccionar al menos un producto.',
            'selected_products.min' => 'Debes seleccionar al menos un producto.',
        ]);

        $selectedProducts = collect($data['selected_products']);

        $invalidProducts = $selectedProducts->filter(function ($code) use ($configuredProducts) {
            return !$configuredProducts->has($code);
        });

        if ($invalidProducts->isNotEmpty()) {
            return back()
                ->withErrors(['selected_products' => 'Uno de los productos seleccionados no pertenece a esta categoría.'])
                ->withInput();
        }

        DB::transaction(function () use ($data, $selectedProducts, $configuredProducts, $controlledProduct) {
            foreach ($selectedProducts as $productCode) {
                $configuredProduct = $configuredProducts->get($productCode);
                $productData = $data['products'][$productCode] ?? [];

                $productName = trim((string) $configuredProduct['name']);
                $manualPreviousStock = $productData['previous_stock'] ?? null;

                $unitsPerBox = max(1, (int) ($productData['units_per_box'] ?? $configuredProduct['units_per_box'] ?? 1));
                $boxesReceived = max(0, (int) ($productData['boxes_received'] ?? 0));
                $exitBoxes = max(0, (int) ($productData['exits'] ?? 0));

                $newEntries = $unitsPerBox * $boxesReceived;
                $newExits = $unitsPerBox * $exitBoxes;

                $newInvoiceNumber = trim((string) ($data['invoice_number'] ?? ''));
                $newDrugstore = trim((string) ($data['drugstore'] ?? ''));

                $existingItem = $controlledProduct->items()
                    ->whereRaw('LOWER(TRIM(product_name)) = ?', [mb_strtolower($productName)])
                    ->first();

                if ($existingItem) {
                    $previousStock = $manualPreviousStock !== null && $manualPreviousStock !== ''
                        ? max(0, (int) $manualPreviousStock)
                        : (int) $existingItem->previous_stock;

                    $totalBoxesReceived = (int) $existingItem->boxes_received + $boxesReceived;
                    $totalEntries = (int) $existingItem->entries + $newEntries;
                    $totalExits = (int) $existingItem->exits + $newExits;

                    $existingItem->update([
                        'drugstore' => $this->mergeTextValues($existingItem->drugstore, $newDrugstore),
                        'invoice_number' => $this->mergeTextValues($existingItem->invoice_number, $newInvoiceNumber),
                        'units_per_box' => $unitsPerBox,
                        'boxes_received' => $totalBoxesReceived,
                        'pills_received' => 0,
                        'entries' => $totalEntries,
                        'exits' => $totalExits,
                        'current_stock' => ControlledProductReportItem::calculateCurrentStock(
                            $previousStock,
                            $totalEntries,
                            $totalExits
                        ),
                    ]);

                    continue;
                }

                $defaultPreviousStock = $this->previousStockForProduct($controlledProduct, $productName);

                $previousStock = $manualPreviousStock !== null && $manualPreviousStock !== ''
                    ? max(0, (int) $manualPreviousStock)
                    : $defaultPreviousStock;

                $controlledProduct->items()->create([
                    'product_name' => $productName,
                    'drugstore' => $newDrugstore !== '' ? $newDrugstore : null,
                    'invoice_number' => $newInvoiceNumber !== '' ? $newInvoiceNumber : null,
                    'units_per_box' => $unitsPerBox,
                    'boxes_received' => $boxesReceived,
                    'pills_received' => 0,
                    'previous_stock' => $previousStock,
                    'entries' => $newEntries,
                    'exits' => $newExits,
                    'current_stock' => ControlledProductReportItem::calculateCurrentStock(
                        $previousStock,
                        $newEntries,
                        $newExits
                    ),
                ]);
            }
        });

        return redirect()
            ->route('admin.controlled-products.show', $controlledProduct)
            ->with('success', 'Productos seleccionados agregados correctamente.');
    }

    public function updateItems(Request $request, ControlledProductReport $controlledProduct)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],

            'items.*.invoice_number' => ['nullable', 'string', 'max:255'],
            'products.*.units_per_box' => ['nullable', 'regex:/^\d+$/'],
            'products.*.boxes_received' => ['nullable', 'regex:/^\d+$/'],
            'products.*.exits' => ['nullable', 'regex:/^\d+$/'],
        ]);

        DB::transaction(function () use ($data, $controlledProduct) {
            foreach ($data['items'] as $itemId => $itemData) {
                $item = $controlledProduct->items()
                    ->whereKey($itemId)
                    ->first();

                if (!$item) {
                    continue;
                }

                $unitsPerBox = max(1, (int) ($itemData['units_per_box'] ?? 1));
                $boxesReceived = max(0, (int) ($itemData['boxes_received'] ?? 0));
                $exitBoxes = max(0, (int) ($itemData['exits'] ?? 0));

                $previousStock = (int) $item->previous_stock;
                $entries = $unitsPerBox * $boxesReceived;
                $exits = $unitsPerBox * $exitBoxes;

                $item->update([
                    'invoice_number' => $itemData['invoice_number'] ?? null,
                    'units_per_box' => $unitsPerBox,
                    'boxes_received' => $boxesReceived,
                    'entries' => $entries,
                    'exits' => $exits,
                    'current_stock' => ControlledProductReportItem::calculateCurrentStock(
                        $previousStock,
                        $entries,
                        $exits
                    ),
                ]);
            }
        });

        return redirect()
            ->route('admin.controlled-products.show', $controlledProduct)
            ->with('success', 'Cambios guardados correctamente.');
    }

    public function destroyItem(ControlledProductReportItem $item)
    {
        $report = $item->report;

        $item->delete();

        return redirect()
            ->route('admin.controlled-products.show', $report)
            ->with('success', 'Producto eliminado del reporte correctamente.');
    }

    public function exportPdf(ControlledProductReport $controlledProduct)
    {
        $controlledProduct->load([
            'items' => function ($query) {
                $query->orderBy('product_name');
            },
        ]);

        $fileName = 'reporte-productos-controlados-' .
            ($controlledProduct->report_number ?? $controlledProduct->id) .
            '.pdf';

        return Pdf::loadView('admin.controlled-products.pdf', [
            'report' => $controlledProduct,
        ])
            ->setPaper('letter', 'landscape')
            ->stream($fileName);
    }

    private function controlledCategories(): array
    {
        return [
            'Psicotrópicos',
            'Estupefacientes',
            'Codeínas y sus sales',
            'Misoprostol',
            'Oxazepam',
            'Morfina',
            'Fentanilo',
        ];
    }

    public function showMonth(string $month)
    {
        $categories = $this->controlledCategories();

        $reports = ControlledProductReport::query()
            ->withCount('items')
            ->where('report_month', $month)
            ->get()
            ->sortBy(function ($report) use ($categories) {
                $position = array_search($report->category, $categories, true);

                return $position === false ? 999 : $position;
            })
            ->values();

        $reportsByCategory = $reports->keyBy('category');

        return view('admin.controlled-products.month-show', compact(
            'month',
            'categories',
            'reports',
            'reportsByCategory'
        ));
    }

    public function exportMonthPdf(string $month)
    {
        $categories = $this->controlledCategories();

        $reports = ControlledProductReport::query()
            ->with([
                'items' => function ($query) {
                    $query->orderBy('product_name');
                },
            ])
            ->where('report_month', $month)
            ->get()
            ->sortBy(function ($report) use ($categories) {
                $position = array_search($report->category, $categories, true);

                return $position === false ? 999 : $position;
            })
            ->values();

        $reportsByCategory = $reports->keyBy('category');

        $fileName = 'reporte-productos-controlados-' . $month . '.pdf';

        return Pdf::loadView('admin.controlled-products.month-pdf', [
            'month' => $month,
            'categories' => $categories,
            'reportsByCategory' => $reportsByCategory,
        ])
            ->setPaper('letter', 'portrait')
            ->stream($fileName);
    }


    private function createReportForCategory(array $data, string $category): ControlledProductReport
    {
        $category = trim($category);

        $previousReport = ControlledProductReport::query()
            ->with('items')
            ->whereRaw('LOWER(TRIM(category)) = ?', [mb_strtolower($category)])
            ->where('report_month', '<', $data['report_month'])
            ->orderByDesc('report_month')
            ->orderByDesc('id')
            ->first();

        $report = ControlledProductReport::create([
            'title' => ($data['title'] ?? null) ?: 'Reporte de productos controlados',
            'report_month' => $data['report_month'],
            'category' => $category,
            'notes' => $data['notes'] ?? null,
        ]);

        $report->update([
            'report_number' => 'PC-' . str_pad($report->id, 6, '0', STR_PAD_LEFT),
        ]);

        if ($previousReport) {
            foreach ($previousReport->items as $previousItem) {
                $previousStock = (int) $previousItem->current_stock;

                $report->items()->create([
                    'product_name' => $previousItem->product_name,
                    'drugstore' => null,
                    'invoice_number' => null,
                    'units_per_box' => (int) ($previousItem->units_per_box ?: 1),
                    'boxes_received' => 0,
                    'pills_received' => 0,
                    'previous_stock' => $previousStock,
                    'entries' => 0,
                    'exits' => 0,
                    'current_stock' => $previousStock,
                ]);
            }
        }

        return $report;
    }

    public function storeMonth(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'report_month' => ['required', 'string', 'max:7'],
            'notes' => ['nullable', 'string'],
        ]);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($data, &$created, &$skipped) {
            foreach ($this->controlledCategories() as $category) {
                $alreadyExists = ControlledProductReport::query()
                    ->where('report_month', $data['report_month'])
                    ->whereRaw('LOWER(TRIM(category)) = ?', [mb_strtolower($category)])
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;
                    continue;
                }

                $this->createReportForCategory($data, $category);
                $created++;
            }
        });

        return redirect()
            ->route('admin.controlled-products.month.show', $data['report_month'])
            ->with(
                'success',
                "Reporte mensual preparado correctamente. Categorías creadas: {$created}. Categorías ya existentes: {$skipped}."
            );
    }

    private function configuredProductsForCategory(?string $category): array
    {
        $category = trim((string) $category);

        return collect(config('controlled_products.' . $category, []))
            ->filter(function ($product) {
                return (bool) ($product['is_active'] ?? true);
            })
            ->sortBy(function ($product) {
                return (int) ($product['sort_order'] ?? 0);
            })
            ->values()
            ->all();
    }

    private function previousStockForProduct(ControlledProductReport $report, string $productName): int
    {
        $productName = trim($productName);
        $category = trim((string) $report->category);

        $previousItem = ControlledProductReportItem::query()
            ->select('controlled_product_report_items.*')
            ->join(
                'controlled_product_reports',
                'controlled_product_reports.id',
                '=',
                'controlled_product_report_items.controlled_product_report_id'
            )
            ->whereRaw('LOWER(TRIM(controlled_product_report_items.product_name)) = ?', [
                mb_strtolower($productName),
            ])
            ->whereRaw('LOWER(TRIM(controlled_product_reports.category)) = ?', [
                mb_strtolower($category),
            ])
            ->where(function ($query) use ($report) {
                if ($report->report_month) {
                    $query->where('controlled_product_reports.report_month', '<', $report->report_month);
                } else {
                    $query->where('controlled_product_reports.id', '<', $report->id);
                }
            })
            ->orderByDesc('controlled_product_reports.report_month')
            ->orderByDesc('controlled_product_reports.id')
            ->first();

        return (int) ($previousItem?->current_stock ?? 0);
    }

    private function mergeTextValues(?string $currentValue, ?string $newValue): ?string
    {
        $currentValue = trim((string) $currentValue);
        $newValue = trim((string) $newValue);

        if ($newValue === '') {
            return $currentValue !== '' ? $currentValue : null;
        }

        $values = collect(
            preg_split('/\s*[,;|]\s*/', $currentValue, -1, PREG_SPLIT_NO_EMPTY)
        )
            ->map(fn($value) => trim($value))
            ->filter()
            ->values();

        $alreadyExists = $values->contains(function ($value) use ($newValue) {
            return mb_strtolower($value) === mb_strtolower($newValue);
        });

        if (!$alreadyExists) {
            $values->push($newValue);
        }

        return $values->isNotEmpty()
            ? $values->implode(', ')
            : null;
    }






}