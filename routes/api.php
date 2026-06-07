<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/checkout', [TransactionController::class, 'checkout']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products',[ProductController::class, 'index']);
    Route::get('/products/{product}',[ProductController::class, 'show']);
});
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/categories/all',[CategoryController::class, 'all']);
    Route::apiResource('categories', CategoryController::class);

    Route::post('/products',[ProductController::class, 'store']);
    Route::put('/products/{product}',[ProductController::class, 'update']);
    Route::delete('/products/{product}',[ProductController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::get('/cashier-summary',[ReportController::class, 'cashierSummary']);
});
Route::middleware(['auth:sanctum','admin'])->prefix('reports')->group(function () {
    Route::get('/total-sales',[ReportController::class, 'totalSales']);
    Route::get('/best-selling-products',[ReportController::class, 'bestSellingProducts']);
    Route::get('/low-stock-products',[ReportController::class, 'lowStockProducts']);
    Route::get('/dashboard-summary',[ReportController::class, 'dashboardSummary']);
});