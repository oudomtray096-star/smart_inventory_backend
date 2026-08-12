<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of sales.
     */
    public function index(Request $request)
    {
        $query = Sale::with([
            'user',
            'items.product',
        ]);

        // Search by sale ID
        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->filled('sale_date')) {
            $query->whereDate('sale_date', $request->sale_date);
        }

        $sales = $query
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }

    /**
     * Store a newly created sale.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_date' => [
                'required',
                'date',
            ],

            'status' => [
                'nullable',
                'in:COMPLETED,CANCELLED',
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

        $status = $validated['status'] ?? 'COMPLETED';

        $sale = DB::transaction(function () use ($validated, $status) {

            /*
             * Check stock before creating the sale.
             */
            foreach ($validated['items'] as $item) {

                $product = Product::lockForUpdate()
                    ->findOrFail($item['product_id']);

                if (
                    $status === 'COMPLETED' &&
                    $product->quantity < $item['quantity']
                ) {
                    abort(
                        422,
                        "Not enough stock for product: {$product->name}"
                    );
                }
            }

            /*
             * Calculate total.
             */
            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $totalAmount +=
                    $item['quantity'] * $item['unit_price'];
            }

            /*
             * Create sale.
             */
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'sale_date' => $validated['sale_date'],
                'total_amount' => $totalAmount,
                'status' => $status,
            ]);

            /*
             * Create sale items
             * and decrease stock.
             */
            foreach ($validated['items'] as $item) {

                $subtotal =
                    $item['quantity'] * $item['unit_price'];

                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,a
                ]);

                if ($status === 'COMPLETED') {

                    $product = Product::lockForUpdate()
                        ->findOrFail($item['product_id']);

                    $product->decrement(
                        'quantity',
                        $item['quantity']
                    );
                }
            }

            return $sale;
        });

        $sale->load([
            'user',
            'items.product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully.',
            'data' => $sale,
        ], 201);
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale)
    {
        $sale->load([
            'user',
            'items.product',
        ]);

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    /**
     * Update the specified sale.
     */
    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'sale_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'status' => [
                'sometimes',
                'required',
                'in:COMPLETED,CANCELLED',
            ],
        ]);

        /*
         * For now, we don't change sale items
         * from this endpoint.
         */
        $sale->update($validated);

        $sale->load([
            'user',
            'items.product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully.',
            'data' => $sale,
        ]);
    }

    /**
     * Remove the specified sale.
     */
    public function destroy(Sale $sale)
    {
        /*
         * Don't delete completed sales because
         * they represent real inventory history.
         */
        if ($sale->status === 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Completed sales cannot be deleted.',
            ], 409);
        }

        $sale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sale deleted successfully.',
        ]);
    }
}