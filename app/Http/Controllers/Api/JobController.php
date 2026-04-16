<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * =====================================================
     * BUAT JOB (PELAGGAN)
     * =====================================================
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

        $service = \App\Models\Service::with('category')->findOrFail($request->service_id);

        $job = Job::create([
            'user_id'     => $user->id,
            'service_id'  => $service->id,
            'category_id' => $service->category_id,
            'deskripsi'   => $request->deskripsi,
            'price'       => $request->price,
            'status'      => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil dibuat',
            'data'    => $this->formatJob($job->load(['service', 'category', 'user.customerProfile']))
        ], 201);
    }

    /**
     * =====================================================
     * JOB MILIK PELANGGAN
     * =====================================================
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
            'data' => $jobs->map(fn($job) => $this->formatJob($job))
        ]);
    }

    /**
     * =====================================================
     * JOB UNTUK TUKANG (PENTING 🔥)
     * =====================================================
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

        $jobs = Job::with([
            'user.customerProfile', // 🔥 ambil alamat
            'service',
            'category'
        ])
        ->where('status', 'pending')
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $jobs->map(fn($job) => $this->formatJob($job))
        ]);
    }

    /**
     * =====================================================
     * TERIMA JOB
     * =====================================================
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
            'data' => $this->formatJob($job->load(['user.customerProfile', 'service']))
        ]);
    }

    /**
     * =====================================================
     * UPDATE STATUS
     * =====================================================
     */
    public function updateStatus(Request $request, $id)
    {
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
            'data' => $this->formatJob($job->load(['user.customerProfile']))
        ]);
    }

    /**
     * =====================================================
     * FORMAT RESPONSE (🔥 BIAR CLEAN)
     * =====================================================
     */
    private function formatJob($job)
    {
        return [
            'id' => $job->id,
            'title' => $job->service->name ?? 'Service',
            'description' => $job->deskripsi,
            'price' => $job->price,
            'status' => $job->status,

            'customer_name' => $job->user->name ?? '-',

            // 🔥 LOKASI DARI CUSTOMER PROFILE
            'location' => $job->user->customerProfile->alamat ?? 'Alamat tidak tersedia',

            'category' => $job->category->name ?? '-',
            'created_at' => $job->created_at,
        ];
    }
}