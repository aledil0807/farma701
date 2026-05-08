<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    public function getOfficialUsdToBsRate(): float
    {
        $response = Http::get('https://ve.dolarapi.com/v1/dolares/oficial');

        if (! $response->successful()) {
            throw new \Exception('No se pudo obtener la tasa del dólar.');
        }

        $data = $response->json();

        if (! isset($data['promedio'])) {
            throw new \Exception('La API no devolvió el campo promedio.');
        }

        return (float) $data['promedio'];
    }
}