<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function checkout(StoreTransactionRequest $request)
    {
        $transaction = DB::transaction(function () use ($request) {

            $totalPrice = 0;

            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'total_price' => 0,
                'payment_method' => $request->payment_method,
            ]);

            foreach ($request->items as $item) {

                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'stock' => [
                            "Insufficient stock for product: {$product->name}"
                        ]
                    ]);
                }

                $subtotal = $product->price * $item['quantity'];
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $item['quantity']);

                $totalPrice += $subtotal;
            }

            $transaction->update([
                'total_price' => $totalPrice,
            ]);

            return $transaction;
        });

        $transaction->load([
            'user',
            'details.product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction,
        ], 201);
    }

    public function index()
    {
        $query = Transaction::with('user')
            ->whereDate(
                'created_at',
                today()
            );

        if (auth()->user()->role === 'cashier') {
            $query->where(
                'user_id',
                auth()->id()
            );
        }

        if (request()->filled('payment_method')) {
            $query->where(
                'payment_method',
                request('payment_method')
            );
        }

        $transactions = $query
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Transaction list retrieved successfully',
            'data' => $transactions,
        ]);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load([
            'user',
            'details.product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction retrieved successfully',
            'data' => $transaction,
        ]);
    }
}