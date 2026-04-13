<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\CategoryController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/password', [AuthController::class, 'changePassword']);
    Route::delete('/user', [AuthController::class, 'deleteAccount']);
    
    Route::apiResource('wallets', WalletController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::get('/transactions/export', [App\Http\Controllers\TransactionController::class, 'export']);
    Route::post('/transactions/export-email', [App\Http\Controllers\TransactionController::class, 'sendExportMail']);
    Route::apiResource('transactions', App\Http\Controllers\TransactionController::class);
    Route::apiResource('goals', App\Http\Controllers\GoalController::class);

    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);

    Route::get('/statistics/spending', [App\Http\Controllers\StatisticController::class, 'spending']);
    Route::get('/statistics/pie-chart', [App\Http\Controllers\StatisticController::class, 'pieChart']);
    Route::get('/statistics/summary', [App\Http\Controllers\StatisticController::class, 'summary']);
    Route::get('/statistics/goals', [App\Http\Controllers\StatisticController::class, 'goals']);
});
