<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_producto_valido()
    {
        $response = $this->postJson('/api/products', [
            'nombre' => 'Taladro',
            'marca' => 'Bosch',
            'codigo' => 'BOSCH1001',
            'stock' => 10,
            'moneda' => 'CLP',
            'precio' => 59990,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['codigo' => 'BOSCH1001']);
    }

    public function test_no_se_puede_crear_producto_con_codigo_repetido()
    {
        Product::factory()->create(['codigo' => 'BOSCH1001']);

        $response = $this->postJson('/api/products', [
            'nombre' => 'Otro',
            'marca' => 'Otra',
            'codigo' => 'BOSCH1001',
            'stock' => 5,
            'moneda' => 'CLP',
            'precio' => 9990,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('codigo');
    }

    public function test_eliminar_producto()
    {
        $producto = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$producto->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $producto->id]);
    }

    public function test_listar_productos()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_mostrar_producto_existente()
    {
        $producto = Product::factory()->create();

        $response = $this->getJson("/api/products/{$producto->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $producto->id,
                'nombre' => $producto->nombre,
            ]);
    }

    public function test_mostrar_producto_no_existente()
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404);
    }

    public function test_actualizar_producto_valido()
    {
        $producto = Product::factory()->create();

        $data = [
            'nombre' => 'Taladro actualizado',
            'precio' => 79990,
            'moneda' => 'CLP', // requerido en update
        ];

        $response = $this->putJson("/api/products/{$producto->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment($data);

        $this->assertDatabaseHas('products', array_merge(['id' => $producto->id], $data));
    }

    public function test_actualizar_producto_con_datos_invalidos()
    {
        $producto = Product::factory()->create();

        $response = $this->putJson("/api/products/{$producto->id}", [
            'precio' => -100, // inválido
            'moneda' => 'INVALID', // inválido
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['precio', 'moneda']);
    }

    public function test_actualizar_producto_no_existente()
    {
        $response = $this->putJson('/api/products/999', [
            'nombre' => 'No existe',
            'moneda' => 'CLP',
        ]);

        $response->assertStatus(404);
    }
}
