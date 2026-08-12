<?php

namespace App\Http\Controllers\Api;
use Illuminate\Routing\Controller;
use App\Models\SaleItem;
use Illuminate\Http\Request;

class SaleItemController extends Controller
{
    /**
     * Display a listing of sale items.
     */
    public function index(Request $request)
    {
        $query = SaleItem::with([
            'sale',
            'product',
        ]);

        // Filter by sale
        if ($request->filled('sale_id')) {
            $query->where('sale_id', $request->sale_id);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $items = $query
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Store a newly created sale item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => [
                'required',
                'exists:sales,id',
            ],

            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $sale = \App\Models\Sale::findOrFail(
            $validated['sale_id']
        );

        // Don't allow adding items to cancelled sales
        if ($sale->status === 'CANCELLED') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add items to a cancelled sale.',
            ], 409);
        }

        $subtotal =
            $validated['quantity'] * $validated['unit_price'];

        $item = SaleItem::create([
            'sale_id' => $validated['sale_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'subtotal' => $subtotal,
        ]);

        // Recalculate sale total
        $totalAmount = $sale->items()->sum('subtotal');

        $sale->update([
            'total_amount' => $totalAmount,
        ]);

        $item->load([
            'sale',
            'product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale item created successfully.',
            'data' => $item,
        ], 201);
    }

    /**
     * Display the specified sale item.
     */
    public function show(SaleItem $saleItem)
    {
        $saleItem->load([
            'sale',
            'product',
        ]);

        return response()->json([
            'success' => true,
            'data' => $saleItem,
        ]);
    }

    /**
     * Update the specified sale item.
     */
    public function update(
        Request $request,
        SaleItem $saleItem
    ) {
        $sale = $saleItem->sale;

        if ($sale->status === 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Completed sale items cannot be modified.',
            ], 409);
        }

        $validated = $request->validate([
            'product_id' => [
                'sometimes',
                'required',
                'exists:products,id',
            ],

            'quantity' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],

            'unit_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $quantity = $validated['quantity']
            ?? $saleItem->quantity;

        $unitPrice = $validated['unit_price']
            ?? $saleItem->unit_price;

        $validated['subtotal'] =
            $quantity * $unitPrice;

        $saleItem->update($validated);

        // Recalculate sale total
        $totalAmount = $sale->items()->sum('subtotal');

        $sale->update([
            'total_amount' => $totalAmount,
        ]);

        $saleItem->load([
            'sale',
            'product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale item updated successfully.',
            'data' => $saleItem,
        ]);
    }

    /**
     * Remove the specified sale item.
     */
    public function destroy(SaleItem $saleItem)
    {
        $sale = $saleItem->sale;

        if ($sale->status === 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Completed sale items cannot be deleted.',
            ], 409);
        }

        $saleItem->delete();

        // Recalculate sale total
        $totalAmount = $sale->items()->sum('subtotal');

        $sale->update([
            'total_amount' => $totalAmount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale item deleted successfully.',
        ]);
    }
}