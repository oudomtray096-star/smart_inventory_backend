<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index()
    {
        $products = Product::with(['category', 'supplier'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'supplier_id' => [
                'nullable',
                'exists:suppliers,id',
            ],

            'sku' => [
                'required',
                'string',
                'max:50',
                'unique:products,sku',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'minimum_stock' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Upload Product Image
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('image')) {
            $validated['image'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Create Product
        |--------------------------------------------------------------------------
        */

        $product = Product::create($validated);

        /*
        |--------------------------------------------------------------------------
        | Return Product
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $product->load([
                'category',
                'supplier',
            ]),
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load([
            'category',
            'supplier',
        ]);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => [
                'sometimes',
                'required',
                'exists:categories,id',
            ],

            'supplier_id' => [
                'sometimes',
                'nullable',
                'exists:suppliers,id',
            ],

            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'unique:products,sku,' . $product->id,
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'purchase_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'selling_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'quantity' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
            ],

            'minimum_stock' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],

            'image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Replace Product Image
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('image')) {

            // Delete old image if it exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            // Store new image
            $validated['image'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Update Product
        |--------------------------------------------------------------------------
        */

        $product->update($validated);

        /*
        |--------------------------------------------------------------------------
        | Return Updated Product
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $product->fresh()->load([
                'category',
                'supplier',
            ]),
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        /*
        |--------------------------------------------------------------------------
        | Delete Product Image
        |--------------------------------------------------------------------------
        */

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Product
        |--------------------------------------------------------------------------
        */

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}