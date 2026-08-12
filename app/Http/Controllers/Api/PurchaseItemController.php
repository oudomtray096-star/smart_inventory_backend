<?php

namespace App\Http\Controllers\Api;

use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class PurchaseItemController extends Controller
{
    /**
     * Display a listing of purchase items.
     */
    public function index(Request $request)
    {
        $query = PurchaseItem::with([
            'purchase',
            'product',
        ]);

        // Filter by purchase
        if ($request->filled('purchase_id')) {
            $query->where('purchase_id', $request->purchase_id);
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
     * Store a newly created purchase item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => [
                'required',
                'exists:purchases,id',
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

        $subtotal = $validated['quantity'] * $validated['unit_price'];

        $item = PurchaseItem::create([
            'purchase_id' => $validated['purchase_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'subtotal' => $subtotal,
        ]);

        $item->load([
            'purchase',
            'product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase item created successfully.',
            'data' => $item,
        ], 201);
    }

    /**
     * Display the specified purchase item.
     */
    public function show(PurchaseItem $purchaseItem)
    {
        $purchaseItem->load([
            'purchase',
            'product',
        ]);

        return response()->json([
            'success' => true,
            'data' => $purchaseItem,
        ]);
    }

    /**
     * Update the specified purchase item.
     */
    public function update(
        Request $request,
        PurchaseItem $purchaseItem
    ) {
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
            ?? $purchaseItem->quantity;

        $unitPrice = $validated['unit_price']
            ?? $purchaseItem->unit_price;

        $validated['subtotal'] = $quantity * $unitPrice;

        $purchaseItem->update($validated);

        $purchaseItem->load([
            'purchase',
            'product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase item updated successfully.',
            'data' => $purchaseItem,
        ]);
    }

    /**
     * Remove the specified purchase item.
     */
    public function destroy(PurchaseItem $purchaseItem)
    {
        $purchase = $purchaseItem->purchase;

        $purchaseItem->delete();

        // Recalculate purchase total
        $totalAmount = $purchase->items()->sum('subtotal');

        $purchase->update([
            'total_amount' => $totalAmount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase item deleted successfully.',
        ]);
    }
}