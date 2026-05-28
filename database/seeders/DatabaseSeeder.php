<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sales')->delete();
        DB::table('products')->delete();
        DB::table('users')->delete();

        // ── UTILISATEURS ──────────────────────────────────────────
        User::create([
            'name'     => 'Administrateur',
            'email'    => 'admin@cinquantenaire.com',
            'password' => bcrypt('admin123'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Manager',
            'email'    => 'manager@cinquantenaire.com',
            'password' => bcrypt('manager123'),
            'role'     => 'manager',
        ]);

        User::create([
            'name'     => 'Alice',
            'email'    => 'alice@cinquantenaire.com',
            'password' => bcrypt('alice123'),
            'role'     => 'caissier',
        ]);

        User::create([
            'name'     => 'Bob',
            'email'    => 'bob@cinquantenaire.com',
            'password' => bcrypt('bob123'),
            'role'     => 'caissier',
        ]);
    }
}
