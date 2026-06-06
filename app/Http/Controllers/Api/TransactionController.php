<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;

class TransactionController extends Controller
{
    public function checkout(StoreTransactionRequest $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Checkout request validated successfully',
            'data' => $request->validated(),
        ]);
    }
}