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
        return response()->json([
            'success' => true,
            'message' => 'Total sales report retrieved successfully',
            'data' => [
                'total_sales' => Transaction::sum('total_price'),
                'total_transactions' => Transaction::count(),
            ],
        ]);
    }

    public function bestSellingProducts(): JsonResponse
    {
        $products = TransactionDetail::join(
                'products',
                'transaction_details.product_id',
                '=',
                'products.id'
            )
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(transaction_details.quantity) as total_sold')
            )
            ->groupBy(
                'products.id',
                'products.name'
            )
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
        $threshold = 5;

        $products = Product::select(
                'id',
                'name',
                'stock'
            )
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Low stock products report retrieved successfully',
            'data' => [
                'threshold' => $threshold,
                'products' => $products,
            ],
        ]);
    }
}