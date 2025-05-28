<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\WebpayPlus\Exceptions\TransactionCommitException;

class ClientOrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::create([
                'type' => 'client',
                'status' => 'pending',
            ]);

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json(['error' => "Producto {$product->nombre} sin stock suficiente."], 400);
                }

                $product->stock -= $item['quantity'];
                $product->save();

                $order->products()->attach($product->id, ['quantity' => $item['quantity']]);

                if (strtolower($product->moneda) === 'clp') {
                    $tipoCambio = 1.0;
                } else {
                    $from = strtolower($product->moneda);
                    $to = 'clp';

                    $url1 = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/{$from}.json";
                    $url2 = "https://latest.currency-api.pages.dev/v1/currencies/{$from}.json";

                    $response = Http::get($url1);

                    if (!$response->ok()) {
                        // fallback
                        $response = Http::get($url2);
                    }

                    if ($response->ok()) {
                        $json = $response->json();

                        $fromKey = strtolower($from);

                        if (isset($json[$fromKey]) && isset($json[$fromKey][$to])) {
                            $tipoCambio = $json[$fromKey][$to];
                        } else {
                            Log::error("No se encontró la moneda destino '{$to}' dentro de la moneda origen '{$fromKey}' en la respuesta");
                            DB::rollBack();
                            return response()->json([
                                'error' => 'No se pudo obtener el tipo de cambio desde la API pública de fawazahmed0',
                                'moneda_origen' => $fromKey,
                                'respuesta_api' => $json,
                            ], 500);
                        }
                    }
                }

                $precioClp = $product->precio * $tipoCambio;
                $totalAmount += $precioClp * $item['quantity'];
                $totalAmount = round($totalAmount);  // Redondear a entero

            }

            $buyOrder = (string) $order->id;
            $sessionId = uniqid();
            $returnUrl = env('APP_URL') . "/client-orders/{$order->id}/webpay/response";

            $transaction = new Transaction();
            $response = $transaction->create($buyOrder, $sessionId, $totalAmount, $returnUrl);

            DB::commit();

            return view('client-orders.webpay', [
                'url' => $response->getUrl(),
                'token' => $response->getToken(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error creando pedido.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function webpayResponse(Request $request, $id)
    {
        $token = $request->input('token_ws');

        if (!$token) {
            return response()->json(['error' => 'Token no recibido'], 400);
        }

        try {
            $transaction = new Transaction();
            $result = $transaction->commit($token);
        } catch (TransactionCommitException $e) {
            return response()->json([
                'error' => 'Error al confirmar el pago',
                'message' => $e->getMessage(),
            ], 400);
        }

        $order = Order::findOrFail($id);

        if ($result->getStatus() === 'AUTHORIZED') {
            $order->status = 'completed';
            $order->save();

            return response()->json([
                'message' => 'Pago exitoso',
                'transaction' => $result,
            ]);
        } else {
            return response()->json([
                'message' => 'Pago no autorizado',
                'transaction' => $result,
            ], 400);
        }
    }

    public function completeOrder($id)
    {
        $order = Order::with('products')->findOrFail($id);

        return response()->json([
            'message' => 'Pedido completado',
            'order' => $order,
        ]);
    }
}
