<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Product::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'codigo' => ['required', 'string', 'max:100', Rule::unique('products', 'codigo')],
            'stock' => 'required|integer|min:0',
            'moneda' => 'required|string|in:CLP,USD,EUR',
            'precio' => 'required|numeric|min:0',
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return Product::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'nombre'  => 'sometimes|string|max:255',
            'marca'   => 'sometimes|string|max:255',
            'codigo'  => 'sometimes|string|max:100|unique:products,codigo,' . $product->id,
            'stock'   => 'sometimes|integer|min:0',
            'moneda' => 'required|string|in:CLP,USD,EUR',
            'precio'  => 'sometimes|numeric|min:0',
        ]);

        $product->update($validated);
        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Product::destroy($id);
        return response()->json(null, 204);
    }
}
