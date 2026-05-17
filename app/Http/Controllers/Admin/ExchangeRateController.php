<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function edit()
    {
        $currentRate = ExchangeRate::where('is_active', true)
            ->latest('id')
            ->first();

        return view('admin.exchange-rate.edit', [
            'currentRate' => $currentRate,
        ]);
    }

    public function update(Request $request, ExchangeRateService $exchangeRateService)
    {
        $request->validate([
            'rate' => ['required', 'numeric', 'min:0.0001'],
        ], [
            'rate.required' => 'Debes ingresar una tasa.',
            'rate.numeric' => 'La tasa debe ser numérica.',
            'rate.min' => 'La tasa debe ser mayor que cero.',
        ]);

        ExchangeRate::query()->update(['is_active' => false]);

        ExchangeRate::create([
            'rate' => $request->rate,
            'source' => 'manual',
            'is_active' => true,
            'effective_date' => now()->toDateString(),
        ]);

        $exchangeRateService->clearRateCache();

        return back()->with('success', 'La tasa manual fue actualizada correctamente.');
    }
}