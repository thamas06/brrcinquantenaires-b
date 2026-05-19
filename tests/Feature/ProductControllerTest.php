<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_prices()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/products', [
            'name' => 'Prod A',
            'purchase_price' => 10,
            'cost_price' => 12,
            'sale_price' => 15,
        ]);

        $response->assertStatus(201)->assertJsonFragment(['name' => 'Prod A', 'sale_price' => 15]);
        $this->assertDatabaseHas('products', ['name' => 'Prod A', 'sale_price' => 15]);
    }

    public function test_manager_can_create_product_by_copying_source()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $manager = User::factory()->create(['role' => 'manager']);

        // admin creates source product
        $this->actingAs($admin, 'sanctum')->postJson('/api/products', [
            'name' => 'Source',
            'purchase_price' => 5,
            'cost_price' => 6,
            'sale_price' => 8,
        ])->assertStatus(201);

        $src = Product::where('name', 'Source')->first();

        $this->actingAs($manager, 'sanctum')->postJson('/api/products', [
            'source_product_id' => $src->id,
            'declared_for_user_id' => $manager->id,
        ])->assertStatus(201)->assertJsonFragment(['name' => 'Source']);
    }

    public function test_admin_can_set_initial_stock_when_creating()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/products', [
            'name' => 'WithStock',
            'sale_price' => 20,
            'stock' => 50,
        ]);

        $response->assertStatus(201)->assertJsonFragment(['name' => 'WithStock', 'stock' => 50]);
        $this->assertDatabaseHas('products', ['name' => 'WithStock', 'stock' => 50]);
    }
}
