<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;

class SaleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_sell_and_stock_decreases()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'caissier']);

        // create product with some stock
        $product = Product::create(['name' => 'Widget', 'sale_price' => 10, 'cost_price' => 5, 'stock' => 10, 'profit' => 5]);

        $response = $this->actingAs($cashier, 'sanctum')->postJson('/api/sales', [
            'product_id' => $product->id,
            'qty' => 3,
            'employee_id' => $cashier->id,
        ]);

        $response->assertStatus(201)->assertJsonFragment(['qty' => 3]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7]);
    }

    public function test_cannot_sell_more_than_stock()
    {
        $cashier = User::factory()->create(['role' => 'caissier']);
        $product = Product::create(['name' => 'Gadget', 'sale_price' => 5, 'cost_price' => 2, 'stock' => 2, 'profit' => 3]);

        $res = $this->actingAs($cashier, 'sanctum')->postJson('/api/sales', [
            'product_id' => $product->id,
            'qty' => 5,
        ]);

        $res->assertStatus(400)->assertJson(['message' => 'Insufficient stock']);
        // stock remains unchanged
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
    }
}
