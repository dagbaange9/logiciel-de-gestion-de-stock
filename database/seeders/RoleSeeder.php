<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Role::ADMINISTRATEUR => 'Administration du système',
            Role::MAGASINIER => 'Gestion physique du stock',
            Role::VENDEUR => 'Opérations commerciales',
        ];

        foreach ($roles as $name => $description) {
            Role::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description],
            );
        }
    }
}
