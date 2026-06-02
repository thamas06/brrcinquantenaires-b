<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ne pas supprimer les données existantes lors du seed.
        // Crée uniquement les utilisateurs par défaut manquants.

        $defaultUsers = [
            [
                'name'     => 'Administrateur',
                'email'    => 'admin@cinquantenaire.com',
                'password' => bcrypt('admin123'),
                'role'     => 'admin',
            ],
            [
                'name'     => 'Manager',
                'email'    => 'manager@cinquantenaire.com',
                'password' => bcrypt('manager123'),
                'role'     => 'manager',
            ],
            [
                'name'     => 'Alice',
                'email'    => 'alice@cinquantenaire.com',
                'password' => bcrypt('alice123'),
                'role'     => 'caissier',
            ],
            [
                'name'     => 'Bob',
                'email'    => 'bob@cinquantenaire.com',
                'password' => bcrypt('bob123'),
                'role'     => 'caissier',
            ],
        ];

        foreach ($defaultUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
