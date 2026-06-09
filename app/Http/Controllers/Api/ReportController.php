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
        $this->validateDateRange();
        
        $totalSales = $this->applyDateRange(
            Transaction::query()
        )->sum('total_price');

        $totalTransactions = $this->applyDateRange(
            Transaction::query()
        )->count();

        return response()->json([
            'success' => true,
            'message' => 'Total sales report retrieved successfully',
            'data' => [
                'total_sales' => (int) $totalSales,
                'total_transactions' => $totalTransactions,
            ],
        ]);
    }

    public function bestSellingProducts(): JsonResponse
    {
        $this->validateDateRange();

        $products = TransactionDetail::join(
            'products',
            'transaction_details.product_id',
            '=',
            'products.id'
        )
        ->join(
            'transactions',
            'transaction_details.transaction_id',
            '=',
            'transactions.id'
        )
        ->when(
            request('start_date'),
            function ($query) {
                $query->where(
                    'transactions.created_at',
                    '>=',
                    request('start_date') . ' 00:00:00'
                );
            }
        )
        ->when(
            request('end_date'),
            function ($query) {
                $query->where(
                    'transactions.created_at',
                    '<=',
                    request('end_date') . ' 23:59:59'
                );
            }
        )
        ->select(
            'products.id as product_id',
            'products.name as product_name',
            DB::raw(
                'SUM(transaction_details.quantity) as total_sold'
            )
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
        $threshold = 10;

        $products = Product::select(
                'id',
                'name',
                'stock'
            )
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->paginate(10);

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

        $monthlyRevenue = (int) Transaction::whereYear(
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

        $averageOrderValue = $todayTransactions > 0
            ? round($todaySales / $todayTransactions)
            : 0;

        $topSellingProduct = TransactionDetail::join(
                'products',
                'transaction_details.product_id',
                '=',
                'products.id'
            )
            ->join(
                'transactions',
                'transaction_details.transaction_id',
                '=',
                'transactions.id'
            )
            ->whereYear(
                'transactions.created_at',
                now()->year
            )
            ->whereMonth(
                'transactions.created_at',
                now()->month
            )
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(transaction_details.quantity) as total_sold')
            )
            ->groupBy(
                'products.id',
                'products.name'
            )
            ->orderByDesc('total_sold')
            ->first();

            $peakSalesHour = Transaction::selectRaw(
                'HOUR(created_at) as hour,
                 COUNT(*) as total_transactions'
            )
            ->whereDate(
                'created_at',
                today()
            )
            ->groupByRaw(
                'HOUR(created_at)'
            )
            ->orderByDesc(
                'total_transactions'
            )
            ->first();
        
        $peakHourData = null;
        
        if ($peakSalesHour) {
        
            $startHour = str_pad(
                $peakSalesHour->hour,
                2,
                '0',
                STR_PAD_LEFT
            );
        
            $endHour = str_pad(
                ($peakSalesHour->hour + 1) % 24,
                2,
                '0',
                STR_PAD_LEFT
            );
        
            $peakHourData = [
                'hour' => "{$startHour}:00 - {$endHour}:00",
                'transactions' => (int) $peakSalesHour->total_transactions,
            ];
        }
        
        $yesterdayRevenue = Transaction::whereDate(
            'created_at',
            now()->subDay()->toDateString()
        )
        ->sum('total_price');

        if ($yesterdayRevenue == 0) {
            $trendPercentage = $todaySales > 0 ? 100 : 0;
        } else {
            $trendPercentage = round(
                (
                    ($todaySales - $yesterdayRevenue)
                    / $yesterdayRevenue
                ) * 100,
                1
            );
        }

        $trendDirection = $trendPercentage >= 0
            ? 'up'
            : 'down';

        $revenueDifference =
            $todaySales - $yesterdayRevenue;

        return response()->json([
            'success' => true,
            'data' => [
                'today_sales' => $todaySales,
                'today_transactions' => $todayTransactions,
                'monthly_revenue' => $monthlyRevenue,
                'low_stock_alerts' => $lowStockAlerts,
        
                'average_order_value' => $averageOrderValue,
        
                'top_selling_product' => $topSellingProduct
                    ? [
                        'id' => $topSellingProduct->id,
                        'name' => $topSellingProduct->name,
                        'qty' => (int) $topSellingProduct->total_sold,
                    ]
                    : null,
        
                'peak_sales_hour' => $peakHourData,
        
                'revenue_trend' => [
                    'percentage' => round($trendPercentage, 1),
                    'direction' => $trendDirection,
                    'difference' => $revenueDifference,
                ],
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

    private function applyDateRange(
        $query,
        $column = 'created_at'
    ) {
        $startDate = request('start_date');
        $endDate = request('end_date');
    
        if ($startDate) {
            $query->where(
                $column,
                '>=',
                $startDate . ' 00:00:00'
            );
        }
    
        if ($endDate) {
            $query->where(
                $column,
                '<=',
                $endDate . ' 23:59:59'
            );
        }
    
        return $query;
    }

    private function validateDateRange()
    {
        $startDate = request('start_date');
        $endDate = request('end_date');

        if (
            $startDate &&
            $endDate &&
            $endDate < $startDate
        ) {
            abort(
                response()->json([
                    'success' => false,
                    'message' => 'End date must be greater than or equal to start date.',
                ], 422)
            );
        }
    }
}