<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class TukangHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * =====================================
     * HISTORI PEKERJAAN TUKANG
     * GET /api/tukang/history
     * =====================================
     */
    public function history()
    {
        $user = Auth::guard('api')->user();

        if (!$user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya untuk tukang'
            ], 403);
        }

        $jobs = Job::with([
            'user',
            'service.category',
            'transaction',
            'review'
        ])
        ->where('tukang_profile_id', $user->tukangProfile->id)
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    /**
     * =====================================
     * PEKERJAAN TERAKHIR TUKANG
     * GET /api/tukang/last-job
     * =====================================
     */
    public function lastJob()
    {
        $user = Auth::guard('api')->user();

        $job = Job::with([
            'user',
            'service.category',
            'transaction',
            'review'
        ])
        ->where('tukang_profile_id', $user->tukangProfile->id)
        ->latest()
        ->first();

        return response()->json([
            'success' => true,
            'data' => $job
        ]);
    }
}