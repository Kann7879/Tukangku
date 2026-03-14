<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class CustomerHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * =====================================
     * HISTORI PEMESANAN TERAKHIR
     * GET /api/customer/last-order
     * =====================================
     */
    public function lastOrder()
    {
        $user = Auth::guard('api')->user();

        $job = Job::with([
            'service.category',
            'tukangProfile.user',
            'transaction',
            'review'
        ])
        ->where('user_id', $user->id)
        ->latest()
        ->first();

        if (!$job) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada histori pemesanan',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $job
        ]);
    }

    /**
     * =====================================
     * SEMUA HISTORI PEMESANAN
     * GET /api/customer/history
     * =====================================
     */
    public function history()
    {
        $user = Auth::guard('api')->user();

        $jobs = Job::with([
            'service.category',
            'tukangProfile.user',
            'transaction',
            'review'
        ])
        ->where('user_id', $user->id)
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }
}