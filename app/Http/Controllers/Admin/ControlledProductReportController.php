<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ControlledProductReport;
use App\Models\ControlledProductReportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlledProductReportController extends Controller
{
    public function index()
    {
        $reports = ControlledProductReport::query()
            ->withCount('items')
            ->latest()
            ->paginate(15);

        return view('admin.controlled-products.index', compact('reports'));
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
                        'drugstore' => $previousItem->drugstore,
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
        $controlledProduct->load('items');

        return view('admin.controlled-products.show', [
            'report' => $controlledProduct,
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

    public function updateItem(Request $request, ControlledProductReportItem $item)
    {
        $data = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'units_per_box' => ['required', 'integer', 'min:1'],
            'boxes_received' => ['nullable', 'integer', 'min:0'],
            'exits' => ['required', 'integer', 'min:0'],
        ]);

        $unitsPerBox = (int) $data['units_per_box'];
        $boxesReceived = (int) ($data['boxes_received'] ?? 0);
        $previousStock = (int) $item->previous_stock;
        $entries = $unitsPerBox * $boxesReceived;
        $exits = (int) $data['exits'];

        $item->update([
            'invoice_number' => $data['invoice_number'] ?? null,
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

        return redirect()
            ->route('admin.controlled-products.show', $item->report)
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroyItem(ControlledProductReportItem $item)
    {
        $report = $item->report;

        $item->delete();

        return redirect()
            ->route('admin.controlled-products.show', $report)
            ->with('success', 'Producto eliminado del reporte correctamente.');
    }
}