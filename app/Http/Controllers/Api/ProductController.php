<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    /** * Display a listing of products. */ 
    public function index()
    {
        $products = Product::with(['category', 'supplier'])->latest()->get();
        return response()->json(['success' => true, 'data' => $products,]);
    }
    /** * Store a newly created product. */ 
    public function store(Request $request)
    {
        $validated = $request->validate(['category_id' => 'required|exists:categories,id', 'supplier_id' => 'nullable|exists:suppliers,id', 'name' => 'required|string|max:255', 'sku' => 'required|string|max:100|unique:products,sku', 'description' => 'nullable|string', 'purchase_price' => 'required|numeric|min:0', 'selling_price' => 'required|numeric|min:0', 'quantity' => 'required|integer|min:0', 'reorder_level' => 'nullable|integer|min:0', 'unit' => 'nullable|string|max:50', 'status' => 'nullable|in:active,inactive',]);
        $product = Product::create($validated);
        return response()->json(['success' => true, 'message' => 'Product created successfully.', 'data' => $product->load(['category', 'supplier']),], 201);
    }
    /** * Display the specified product. */ 
    public function show(Product $product)
    {
        $product->load(['category', 'supplier']);
        return response()->json(['success' => true, 'data' => $product,]);
    }
    /** * Update the specified product. */ 
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate(['category_id' => 'sometimes|required|exists:categories,id', 'supplier_id' => 'nullable|exists:suppliers,id', 'name' => 'sometimes|required|string|max:255', 'sku' => 'sometimes|required|string|max:100|unique:products,sku,' . $product->id, 'description' => 'nullable|string', 'purchase_price' => 'sometimes|required|numeric|min:0', 'selling_price' => 'sometimes|required|numeric|min:0', 'quantity' => 'sometimes|required|integer|min:0', 'reorder_level' => 'nullable|integer|min:0', 'unit' => 'nullable|string|max:50', 'status' => 'nullable|in:active,inactive',]);
        $product->update($validated);
        return response()->json(['success' => true, 'message' => 'Product updated successfully.', 'data' => $product->fresh()->load(['category', 'supplier']),]);
    }
    /** * Remove the specified product. */ 
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Product deleted successfully.',]);
    }
}
