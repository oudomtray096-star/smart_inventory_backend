<?php

namespace App\Http\Controllers\Api;


use Illuminate\Routing\Controller;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\StockTransaction;

class DashboardController extends Controller
{
    /**
     * Display dashboard summary.
     */
    public function index()
    {
        $today = now()->toDateString();

        $totalProducts = Product::count();

        $totalCategories = Category::count();

        $totalSuppliers = Supplier::count();

        $lowStock = Product::whereColumn(
            'quantity',
            '<=',
            'minimum_stock'
        )->count();

        $todayStockIn = StockTransaction::where('type', 'IN')
            ->whereDate('created_at', $today)
            ->sum('quantity');

        $todayStockOut = StockTransaction::where('type', 'OUT')
            ->whereDate('created_at', $today)
            ->sum('quantity');

        return response()->json([
            'success' => true,

            'data' => [
                'totalProducts' => $totalProducts,
                'totalCategories' => $totalCategories,
                'totalSuppliers' => $totalSuppliers,
                'lowStock' => $lowStock,
                'todayStockIn' => $todayStockIn,
                'todayStockOut' => $todayStockOut,
            ],
        ]);
    }
}