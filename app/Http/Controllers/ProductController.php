<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Product::with('category')->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sku'         => 'required|string|unique:products,sku',
            'name'        => 'required|string|max:255',
            'min_stock'   => 'nullable|integer|min:0',
        ]);

        $product = Product::create([
            'category_id' => $data['category_id'],
            'sku'         => $data['sku'],
            'name'        => $data['name'],
            'min_stock'   => $data['min_stock'] ?? 5,
        ]);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan',
            'data'    => $product->fresh(),
        ], 201);
    }

    public function show(Product $product)
    {
        return response()->json([
            'data' => $product->load('category'),
        ], 200);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'sku'         => 'sometimes|required|string|unique:products,sku,' . $product->id,
            'name'        => 'sometimes|required|string|max:255',
            'min_stock'   => 'sometimes|integer|min:0',
        ]);

        $product->update($data);

        return response()->json([
            'message' => 'Produk berhasil diperbarui',
            'data'    => $product->fresh(),
        ], 200);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus',
        ], 200);
    }

    public function lowStock()
    {
        $lowStockProducts = Product::with('category')
            ->get()
            ->filter(fn(Product $product) => $product->is_low_stock)
            ->values();

        return response()->json([
            'data' => $lowStockProducts,
        ], 200);
    }
}
