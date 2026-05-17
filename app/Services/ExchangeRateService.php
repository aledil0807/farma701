<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected int $cacheSeconds = 900;

    public function getOfficialUsdToBsRate(): float
    {
        return Cache::remember('usd_to_bs_official_rate', $this->cacheSeconds, function () {
            $manualRate = ExchangeRate::where('is_active', true)
                ->latest('id')
                ->first();

            if ($manualRate) {
                Cache::put('usd_to_bs_official_rate_source', 'manual', $this->cacheSeconds);
                return (float) $manualRate->rate;
            }

            $rate = $this->getRateFromBcvToday();

            if ($rate !== null) {
                Cache::put('usd_to_bs_official_rate_source', 'bcv.today', $this->cacheSeconds);
                return $rate;
            }

            $rate = $this->getRateFromDolarApi();

            if ($rate !== null) {
                Cache::put('usd_to_bs_official_rate_source', 've.dolarapi', $this->cacheSeconds);
                return $rate;
            }

            $lastKnownRate = Cache::get('usd_to_bs_official_rate_last_known');

            if ($lastKnownRate !== null) {
                Cache::put('usd_to_bs_official_rate_source', 'last_known_cache', $this->cacheSeconds);
                return (float) $lastKnownRate;
            }

            throw new \Exception('No se pudo obtener la tasa oficial USD/VES.');
        });
    }

    public function clearRateCache(): void
    {
        Cache::forget('usd_to_bs_official_rate');
        Cache::forget('usd_to_bs_official_rate_source');
    }

    protected function getRateFromBcvToday(): ?float
    {
        try {
            $response = Http::timeout(10)->acceptJson()->get('https://bcv.today/api/v1/rate.json');

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (! isset($data['USD']) || ! is_numeric($data['USD'])) {
                return null;
            }

            $rate = (float) $data['USD'];

            if ($rate <= 0) {
                return null;
            }

            Cache::forever('usd_to_bs_official_rate_last_known', $rate);

            return $rate;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    protected function getRateFromDolarApi(): ?float
    {
        try {
            $response = Http::timeout(10)->acceptJson()->get('https://ve.dolarapi.com/v1/dolares/oficial');

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (! isset($data['promedio']) || ! is_numeric($data['promedio'])) {
                return null;
            }

            $rate = (float) $data['promedio'];

            if ($rate <= 0) {
                return null;
            }

            Cache::forever('usd_to_bs_official_rate_last_known', $rate);

            return $rate;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}