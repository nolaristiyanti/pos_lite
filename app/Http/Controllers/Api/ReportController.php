<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function totalSales(): JsonResponse
    {
        $totalSales = Transaction::sum('total_price');

        return response()->json([
            'success' => true,
            'message' => 'Total sales report retrieved successfully',
            'data' => [
                'total_sales' => $totalSales,
            ],
        ]);
    }

    public function bestSellingProducts(): JsonResponse
    {
        $products = TransactionDetail::select(
                'product_id',
                DB::raw('SUM(quantity) as total_sold')
            )
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Best selling products report retrieved successfully',
            'data' => $products,
        ]);
    }

    public function lowStockProducts(): JsonResponse
    {
        $products = Product::select(
                'id',
                'name',
                'stock'
            )
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Low stock products report retrieved successfully',
            'data' => $products,
        ]);
    }
}