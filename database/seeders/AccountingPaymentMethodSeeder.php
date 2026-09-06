<?php

namespace Database\Seeders;

use App\Models\AccountingPaymentMethod;
use Illuminate\Database\Seeder;

class AccountingPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'cash', 'name' => 'Cash', 'sort_order' => 1],
            ['code' => 'biopago', 'name' => 'Biopago', 'sort_order' => 2],
            ['code' => 'pagomovil', 'name' => 'Pago móvil', 'sort_order' => 3],
            ['code' => 'pdv', 'name' => 'PDV', 'sort_order' => 4],
            ['code' => 'efectivobs', 'name' => 'Efectivo Bs', 'sort_order' => 5],
            ['code' => 'zelle', 'name' => 'Zelle', 'sort_order' => 6],
            ['code' => 'binance', 'name' => 'Binance', 'sort_order' => 7],
        ];

        foreach ($methods as $method) {
            AccountingPaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'sort_order' => $method['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}