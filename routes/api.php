<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ServiceController;

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
