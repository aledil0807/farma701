<?php

namespace App\Providers;

use App\Models\Laboratory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::share('assetVersion', env('ASSET_VERSION', '1.0.0'));

        View::composer(
            ['inicio', 'busqueda', 'carrito', 'laboratories.show'],
            function ($view) {
                $view->with(
                    'laboratoriesNav',
                    Laboratory::orderBy('name')->get()
                );
            }
        );
    }
}