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

    public function dashboardSummary()
    {
        $todaySales = Transaction::whereDate(
            'created_at',
            today()
        )->sum('total_price');

        $todayTransactions = Transaction::whereDate(
            'created_at',
            today()
        )->count();

        $monthlyRevenue = Transaction::whereYear(
            'created_at',
            now()->year
        )
        ->whereMonth(
            'created_at',
            now()->month
        )
        ->sum('total_price');

        $lowStockAlerts = Product::where(
            'stock',
            '<=',
            10
        )->count();

        return response()->json([
            'success' => true,
            'data' => [
                'today_sales' => $todaySales,
                'today_transactions' => $todayTransactions,
                'monthly_revenue' => $monthlyRevenue,
                'low_stock_alerts' => $lowStockAlerts,
            ],
        ]);
    }

    public function cashierSummary()
    {
        $todaySales = Transaction::whereDate(
            'created_at',
            today()
        )
        ->where(
            'user_id',
            auth()->id()
        )
        ->sum('total_price');

        $todayTransactions = Transaction::whereDate(
            'created_at',
            today()
        )
        ->where(
            'user_id',
            auth()->id()
        )
        ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'my_sales_today' => $todaySales,
                'my_transactions_today' => $todayTransactions,
            ],
        ]);
    }
}