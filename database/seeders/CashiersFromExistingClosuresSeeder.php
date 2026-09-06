<?php

namespace Database\Seeders;

use App\Models\AccountingClosure;
use App\Models\Cashier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashiersFromExistingClosuresSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $closures = AccountingClosure::query()
                ->whereNotNull('employee_name')
                ->get();

            foreach ($closures as $closure) {
                $name = trim((string) $closure->employee_name);

                if ($name === '') {
                    continue;
                }

                $cashier = Cashier::firstOrCreate(
                    ['name' => $name],
                    [
                        'is_active' => true,
                        'sort_order' => Cashier::max('sort_order') + 1,
                    ]
                );

                $closure->update([
                    'cashier_id' => $cashier->id,
                ]);
            }
        });
    }
}