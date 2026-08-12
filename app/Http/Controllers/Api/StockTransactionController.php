<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransactionController extends Controller
{
    /**
     * Display a listing of stock transactions.
     */
    public function index(Request $request)
    {
        $query = StockTransaction::with([
            'product',
            'user',
        ]);

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by transaction type
        if ($request->filled('type')) {
            $query->where('type', strtoupper($request->type));
        }

        // Search by product name or SKU
        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $transactions = $query
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Store a new stock adjustment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'type' => [
                'required',
                'in:IN,OUT',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'note' => [
                'nullable',
                'string',
            ],
        ]);

        $transaction = DB::transaction(function () use ($request, $validated) {

            $product = Product::lockForUpdate()
                ->findOrFail($validated['product_id']);

            /*
             * Stock IN
             */
            if ($validated['type'] === 'IN') {

                $product->increment(
                    'quantity',
                    $validated['quantity']
                );
            }

            /*
             * Stock OUT
             */
            if ($validated['type'] === 'OUT') {

                if ($product->quantity < $validated['quantity']) {
                    abort(
                        422,
                        "Not enough stock for product: {$product->name}"
                    );
                }

                $product->decrement(
                    'quantity',
                    $validated['quantity']
                );
            }

            /*
             * Create transaction history.
             */
            return StockTransaction::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'note' => $validated['note'] ?? null,
            ]);
        });

        $transaction->load([
            'product',
            'user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock transaction created successfully.',
            'data' => $transaction,
        ], 201);
    }

    /**
     * Display the specified stock transaction.
     */
    public function show(StockTransaction $stockTransaction)
    {
        $stockTransaction->load([
            'product',
            'user',
        ]);

        return response()->json([
            'success' => true,
            'data' => $stockTransaction,
        ]);
    }

    /**
     * Update the specified stock transaction.
     */
    public function update(
        Request $request,
        StockTransaction $stockTransaction
    ) {
        /*
         * Stock transactions represent historical records.
         *
         * Changing them directly can make inventory
         * calculations incorrect.
         *
         * Therefore, we don't allow updating them.
         */
        return response()->json([
            'success' => false,
            'message' => 'Stock transactions cannot be modified after creation.',
        ], 409);
    }

    /**
     * Remove the specified stock transaction.
     */
    public function destroy(
        StockTransaction $stockTransaction
    ) {
        /*
         * Don't delete inventory history.
         */
        return response()->json([
            'success' => false,
            'message' => 'Stock transactions cannot be deleted.',
        ], 409);
    }
}