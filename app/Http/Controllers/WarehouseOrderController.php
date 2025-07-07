<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WarehouseOrderController extends Controller
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
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "No hay suficiente stock para el producto {$product->nombre}"
                    ], 400);
                }
            }

            $order = Order::create([
                'status' => 'reserved',
                'type' => 'warehouse',
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                $order->products()->attach($product->id, ['quantity' => $item['quantity']]);

                $product->stock -= $item['quantity'];
                $product->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Pedido a bodega creado correctamente',
                'order' => $order->load('products')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear el pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id)
    {
        $order = Order::with('products')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Pedido no encontrado.'], 404);
        }

        if ($order->type !== 'warehouse') {
            return response()->json(['message' => 'Este no es un pedido de bodega.'], 400);
        }

        if ($order->status === 'cancelled') {
            return response()->json(['message' => 'El pedido ya fue cancelado.'], 400);
        }

        foreach ($order->products as $product) {
            $product->stock += $product->pivot->quantity;
            $product->save();
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Pedido de bodega cancelado y stock devuelto.'], 200);
    }
}
