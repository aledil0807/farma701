<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    public function index()
    {
        $cashiers = Cashier::query()
            ->withCount('closures')
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.accounting.cashiers.index', compact('cashiers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        Cashier::create([
            'name' => trim($data['name']),
            'sort_order' => $data['sort_order'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.accounting.cashiers.index')
            ->with('success', 'Cajero creado correctamente.');
    }

    public function toggleStatus(Cashier $cashier)
    {
        $cashier->update([
            'is_active' => ! $cashier->is_active,
        ]);

        return redirect()
            ->route('admin.accounting.cashiers.index')
            ->with('success', $cashier->is_active ? 'Cajero activado correctamente.' : 'Cajero desactivado correctamente.');
    }

    public function destroy(Cashier $cashier)
    {
        if ($cashier->closures()->exists()) {
            $cashier->update([
                'is_active' => false,
            ]);

            return redirect()
                ->route('admin.accounting.cashiers.index')
                ->with('success', 'Este cajero tiene cierres registrados, por seguridad fue desactivado en vez de eliminado.');
        }

        $cashier->delete();

        return redirect()
            ->route('admin.accounting.cashiers.index')
            ->with('success', 'Cajero eliminado correctamente.');
    }
}