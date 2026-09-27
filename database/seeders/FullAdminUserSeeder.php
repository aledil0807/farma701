<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FullAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => '',
            ],
            [
                'name' => 'Administrador completo',
                'password' => Hash::make(''),
                'can_access_accounting' => true,
                'can_access_metrics' => true,
            ]
        );

        $this->command->info('Usuario administrador completo creado o actualizado correctamente.');
    }
}