<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;


class WarehouseOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_pedido_bodega_con_stock_suficiente()
    {
        $producto = Product::factory()->create(['stock' => 10]);

        $response = $this->postJson('/api/warehouse-orders', [
            'items' => [
                ['product_id' => $producto->id, 'quantity' => 5]
            ]
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'reserved']);

        $this->assertDatabaseHas('products', [
            'id' => $producto->id,
            'stock' => 5
        ]);
    }

    public function test_crear_pedido_bodega_con_stock_insuficiente()
    {
        $producto = Product::factory()->create(['stock' => 3]);

        $response = $this->postJson('/api/warehouse-orders', [
            'items' => [
                ['product_id' => $producto->id, 'quantity' => 10]
            ]
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => "No hay suficiente stock para el producto {$producto->nombre}"
            ]);
    }

    public function test_crear_pedido_bodega_con_datos_invalidos()
    {
        $response = $this->postJson('/api/warehouse-orders', [
            'items' => []
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_cancelar_pedido_bodega()
    {
        $producto = Product::factory()->create(['stock' => 10]);

        $this->postJson('/api/warehouse-orders', [
            'items' => [
                ['product_id' => $producto->id, 'quantity' => 5]
            ]
        ]);

        $order = Order::first();

        $response = $this->postJson("/api/warehouse-orders/cancel/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Pedido de bodega cancelado y stock devuelto.'
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $producto->id,
            'stock' => 10
        ]);
    }

    public function test_cancelar_pedido_ya_cancelado()
    {
        $order = Order::factory()->create([
            'status' => 'cancelled',
            'type' => 'warehouse',
        ]);

        $order->refresh();

        $response = $this->postJson("/api/warehouse-orders/cancel/{$order->id}");

        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'El pedido ya fue cancelado.'
            ]);
    }

    public function test_cancelar_pedido_inexistente()
    {
        $response = $this->postJson('/api/warehouse-orders/cancel/999');

        $response->assertStatus(404);
    }
}
