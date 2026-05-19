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
        $admin = User::create([
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

        // ── PRODUITS DE DÉPART ────────────────────────────────────
        $products = [
            ['name' => 'Bière Pression',       'purchase_price' => 500,  'cost_price' => 600,  'sale_price' => 1000, 'stock' => 100],
            ['name' => 'Coca-Cola',             'purchase_price' => 300,  'cost_price' => 400,  'sale_price' => 800,  'stock' => 150],
            ['name' => 'Eau Minérale',          'purchase_price' => 150,  'cost_price' => 200,  'sale_price' => 500,  'stock' => 200],
            ['name' => "Whisky Jack Daniel's",  'purchase_price' => 3000, 'cost_price' => 3500, 'sale_price' => 6000, 'stock' => 50],
            ['name' => 'Vin Rouge',             'purchase_price' => 2000, 'cost_price' => 2500, 'sale_price' => 5000, 'stock' => 60],
            ["name" => "Jus d'Orange",          'purchase_price' => 400,  'cost_price' => 500,  'sale_price' => 900,  'stock' => 120],
            ['name' => 'Café Espresso',         'purchase_price' => 200,  'cost_price' => 300,  'sale_price' => 700,  'stock' => 200],
            ['name' => 'Cocktail Maison',       'purchase_price' => 1500, 'cost_price' => 2000, 'sale_price' => 4000, 'stock' => 80],
        ];

        foreach ($products as $p) {
            DB::table('products')->insert([
                'name'                 => $p['name'],
                'purchase_price'       => $p['purchase_price'],
                'cost_price'           => $p['cost_price'],
                'sale_price'           => $p['sale_price'],
                'profit'               => $p['sale_price'] - $p['cost_price'],
                'stock'                => $p['stock'],
                'declared_by_user_id'  => $admin->id,
                'declared_for_user_id' => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }
}
