<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
//use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Transbank\Webpay\WebpayPlus\Exceptions\TransactionCommitException;
use Transbank\Webpay\WebpayPlus\Transaction;

class ClientOrderController extends Controller
{
    //protected $currencyService;

    //public function __construct(CurrencyConversionService $currencyService)
    //{
    //     $this->currencyService = $currencyService;
    //}

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


                $totalAmount += $product->precio * $item['quantity'];
            }

            $buyOrder = (string) $order->id;
            $sessionId = uniqid();
            $returnUrl = env('APP_URL') . "/client-orders/{$order->id}/webpay/response";

            $transaction = new Transaction();
            $response = $transaction->create($buyOrder, $sessionId, $totalAmount, $returnUrl);

            DB::commit();

            return response()->json([
                'order' => $order->load('products'),
                'webpay' => [
                    'url' => $response->getUrl(),
                    'token' => $response->getToken(),
                ],
            ], 201);

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
