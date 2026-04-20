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
use App\Http\Controllers\Api\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/**
 * =====================================================
 * AUTH API
 * =====================================================
 */
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::get('/categories', [CategoryController::class, 'index']);

/**
 * =====================================================
 * TUKANG PUBLIC API (TANPA LOGIN) 🔥
 * =====================================================
 */
Route::get('/tukang', [TukangProfileController::class, 'index']);              // semua tukang
Route::get('/tukang/top', [TukangProfileController::class, 'top']);            // tukang terbaik
Route::get('/tukang/category/{categoryId}', [TukangProfileController::class, 'byCategory']); // by kategori
Route::get('/tukang/{id}', [TukangProfileController::class, 'showPublic']);    // detail tukang

/**
 * =====================================================
 * SERVICE API (KHUSUS TUKANG)
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/my', [ServiceController::class, 'myServices']);
});

/**
 * =====================================================
 * JOBS API
 * =====================================================
 */
Route::prefix('jobs')->group(function () {
    Route::get('/', [JobController::class, 'index']);
    Route::get('/{id}', [JobController::class, 'show']);
    Route::post('/', [JobController::class, 'store']);
    Route::put('/{id}', [JobController::class, 'update']);
    Route::delete('/{id}', [JobController::class, 'destroy']);
    Route::patch('/{id}/status', [JobController::class, 'updateStatus']);
});

/**
 * =====================================================
 * TRANSACTION API
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/my', [TransactionController::class, 'myTransactions']);
    Route::patch('/transactions/{id}/pay', [TransactionController::class, 'pay']);
});

/**
 * =====================================================
 * REVIEW API
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/tukang', [ReviewController::class, 'myReviews']);
});

/**
 * =====================================================
 * CUSTOMER PROFILE API
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {
    Route::get('/customer/profile', [CustomerProfileController::class, 'show']);
    Route::post('/customer/profile', [CustomerProfileController::class, 'store']);
});

/**
 * =====================================================
 * CUSTOMER HISTORY API
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {
    Route::get('/customer/last-order', [CustomerHistoryController::class, 'lastOrder']);
    Route::get('/customer/history', [CustomerHistoryController::class, 'history']);
});

/**
 * =====================================================
 * TUKANG PROFILE API (KHUSUS TUKANG YANG LOGIN)
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {
    Route::get('/tukang/profile', [TukangProfileController::class, 'show']);
    Route::post('/tukang/profile', [TukangProfileController::class, 'store']);
    Route::get('/tukang/dashboard', [TukangProfileController::class, 'dashboard']);
});

/**
 * =====================================================
 * TUKANG HISTORY API
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {
    Route::get('/tukang/history', [TukangHistoryController::class, 'history']);
    Route::get('/tukang/last-job', [TukangHistoryController::class, 'lastJob']);
});

/**
 * =====================================================
 * MESSAGE API
 * =====================================================
 */
Route::middleware('auth:api')->group(function () {
    Route::post('/messages', [MessageController::class, 'send']);
    Route::get('/messages/{job_id}', [MessageController::class, 'getMessages']);
});