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
            ['components.header', 'inicio', 'busqueda', 'carrito', 'laboratories.show'],
            function ($view) {
                $laboratories = Laboratory::visible()
                    ->orderBy('name')
                    ->get();

                $view->with('laboratoriesNav', $laboratories);
            }
        );
    }
}