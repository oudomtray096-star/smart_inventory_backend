<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of purchases.
     */
    public function index(Request $request)
    {
        $query = Purchase::with([
            'supplier',
            'user',
            'items.product',
        ]);

        // Search by supplier name
        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('supplier', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchases = $query
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $purchases,
        ]);
    }

    /**
     * Store a newly created purchase.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'purchase_date' => [
                'required',
                'date',
            ],

            'status' => [
                'nullable',
                'in:PENDING,RECEIVED,CANCELLED',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $purchase = DB::transaction(function () use ($validated) {

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'user_id' => auth()->id(),
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => $totalAmount,
                'status' => $validated['status'] ?? 'RECEIVED',
            ]);

            foreach ($validated['items'] as $item) {

                $subtotal = $item['quantity'] * $item['unit_price'];

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // Only increase stock when purchase is received
                if (($validated['status'] ?? 'RECEIVED') === 'RECEIVED') {

                    $product = Product::findOrFail($item['product_id']);

                    $product->increment(
                        'quantity',
                        $item['quantity']
                    );
                }
            }

            return $purchase;
        });

        $purchase->load([
            'supplier',
            'user',
            'items.product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase created successfully.',
            'data' => $purchase,
        ], 201);
    }

    /**
     * Display the specified purchase.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'user',
            'items.product',
        ]);

        return response()->json([
            'success' => true,
            'data' => $purchase,
        ]);
    }

    /**
     * Update the specified purchase.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_id' => [
                'sometimes',
                'required',
                'exists:suppliers,id',
            ],

            'purchase_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'status' => [
                'sometimes',
                'required',
                'in:PENDING,RECEIVED,CANCELLED',
            ],
        ]);

        /*
         * For the first version of the system,
         * don't allow changing purchase items here.
         */
        $purchase->update($validated);

        $purchase->load([
            'supplier',
            'user',
            'items.product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase updated successfully.',
            'data' => $purchase,
        ]);
    }

    /**
     * Remove the specified purchase.
     */
    public function destroy(Purchase $purchase)
    {
        if ($purchase->status === 'RECEIVED') {
            return response()->json([
                'success' => false,
                'message' => 'Received purchases cannot be deleted.',
            ], 409);
        }

        $purchase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase deleted successfully.',
        ]);
    }
}