<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\CustomerHistoryController;
use App\Http\Controllers\Api\TukangProfileController;
use App\Http\Controllers\Api\TukangHistoryController;
use App\Http\Controllers\Api\MessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Semua route API aplikasi TukangKu
| Menggunakan JWT Auth (tymon/jwt-auth)
|
*/

/**
 * =====================================================
 * AUTH API (REGISTER, LOGIN, LOGOUT)
 * =====================================================
 */
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {

    // Register user baru (Pelanggan / Tukang)
    Route::post('register', [AuthController::class, 'register']);

    // Login dan ambil JWT token
    Route::post('login', [AuthController::class, 'login']);

    // Logout (invalidate token)
    Route::post('logout', [AuthController::class, 'logout']);

    // Refresh token JWT
    Route::post('refresh', [AuthController::class, 'refresh']);

    // Ambil data user yang sedang login
    Route::post('me', [AuthController::class, 'me']);
});

/**
 * =====================================================
 * SERVICE API (KHUSUS TUKANG)
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {

    // Tambah jasa (Tukang)
    Route::post('/services', [ServiceController::class, 'store']);

    // List jasa milik tukang (Dashboard)
    Route::get('/services/my', [ServiceController::class, 'myServices']);
});

Route::prefix('jobs')->group(function () {

    Route::get('/', [JobController::class, 'index']);
    Route::get('/{id}', [JobController::class, 'show']);
    Route::post('/', [JobController::class, 'store']);
    Route::put('/{id}', [JobController::class, 'update']);
    Route::delete('/{id}', [JobController::class, 'destroy']);

    Route::patch('/{id}/status', [JobController::class, 'updateStatus']);
});

Route::middleware('auth:api')->group(function () {

    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/my', [TransactionController::class, 'myTransactions']);
    Route::patch('/transactions/{id}/pay', [TransactionController::class, 'pay']);

});

Route::middleware('auth:api')->group(function () {

    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/tukang', [ReviewController::class, 'myReviews']);

});

Route::middleware('auth:api')->group(function () {

    Route::get('/customer/profile', [CustomerProfileController::class, 'show']);

    Route::post('/customer/profile', [CustomerProfileController::class, 'store']);

});

Route::middleware('auth:api')->group(function () {

    Route::get('/customer/last-order', [CustomerHistoryController::class, 'lastOrder']);

    Route::get('/customer/history', [CustomerHistoryController::class, 'history']);

});

Route::middleware('auth:api')->group(function () {

    Route::get('/tukang/profile', [TukangProfileController::class, 'show']);
    Route::post('/tukang/profile', [TukangProfileController::class, 'store']);

});

Route::middleware('auth:api')->group(function () {

    Route::get('/tukang/history', [TukangHistoryController::class, 'history']);

    Route::get('/tukang/last-job', [TukangHistoryController::class, 'lastJob']);

});

Route::middleware('auth:api')->group(function () {

    Route::post('/messages', [MessageController::class, 'send']);

    Route::get('/messages/{job_id}', [MessageController::class, 'getMessages']);

});