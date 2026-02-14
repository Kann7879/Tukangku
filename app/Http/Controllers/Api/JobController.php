<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    /**
     * =====================================================
     * CONSTRUCTOR
     * =====================================================
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * =====================================================
     * BUAT JOB (KHUSUS PELANGGAN)
     * =====================================================
     * POST /api/jobs
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user->hasRole('Pelanggan')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pelanggan yang bisa membuat job'
            ], 403);
        }

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'deskripsi'  => 'required|string',
            'price'      => 'required|integer|min:0',
        ]);

        // 🔥 Ambil service
        $service = \App\Models\Service::with('category')->findOrFail($request->service_id);

        $job = Job::create([
            'user_id'     => $user->id,
            'service_id'  => $service->id,
            'category_id' => $service->category_id, // 🔥 ambil dari service, bukan request
            'deskripsi'   => $request->deskripsi,
            'price'       => $request->price,
            'status'      => 'pending'
        ]);

        $job->load(['service', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil dibuat',
            'data'    => $job
        ], 201);
    }

    /**
     * =====================================================
     * LIST JOB MILIK USER
     * =====================================================
     * GET /api/jobs/my
     */
    public function myJobs()
    {
        $user = Auth::guard('api')->user();

        $jobs = Job::with(['service', 'category', 'tukangProfile'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $jobs
        ]);
    }

    /**
     * =====================================================
     * LIST JOB UNTUK TUKANG
     * =====================================================
     * GET /api/jobs/available
     */
    public function availableJobs()
    {
        $user = Auth::guard('api')->user();

        if (! $user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $jobs = Job::with(['user', 'category'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $jobs
        ]);
    }

    /**
     * =====================================================
     * TERIMA JOB (KHUSUS TUKANG)
     * =====================================================
     * PATCH /api/jobs/{id}/accept
     */
    public function acceptJob($id)
    {
        $user = Auth::guard('api')->user();

        if (! $user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya tukang yang bisa menerima job'
            ], 403);
        }

        $job = Job::find($id);

        if (! $job || $job->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Job tidak tersedia'
            ], 400);
        }

        $job->update([
            'tukang_profile_id' => $user->tukangProfile->id,
            'status' => 'diterima'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil diterima',
            'data'    => $job
        ]);
    }

    /**
     * =====================================================
     * UPDATE STATUS JOB
     * =====================================================
     * PATCH /api/jobs/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::guard('api')->user();

        $job = Job::find($id);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Job tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:dikerjakan,selesai,dibatalkan'
        ]);

        $job->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui',
            'data'    => $job
        ]);
    }
}
