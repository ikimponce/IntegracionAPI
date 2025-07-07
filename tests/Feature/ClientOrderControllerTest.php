<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class ClientOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_pedido_cliente_retorna_html_webpay()
    {
        // Simula respuesta de la API de tipo de cambio
        Http::fake([
            '*' => Http::response([
                'usd' => ['clp' => 900], // ejemplo si el producto es en USD
            ], 200)
        ]);

        // Creamos un producto
        $product = Product::factory()->create([
            'stock' => 10,
            'precio' => 100,
            'moneda' => 'CLP',
        ]);

        // Simulamos datos del request
        $payload = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ]
        ];

        // Ejecutamos la petición POST al endpoint
        $response = $this->post('/client-orders', $payload);

        // Aseguramos que devuelva un HTML (vista)
        $response->assertStatus(200);
        $response->assertSee('<form', false); // verifica que haya un form en el HTML
        $response->assertSee('token_ws'); // verifica que esté el token
    }
}
