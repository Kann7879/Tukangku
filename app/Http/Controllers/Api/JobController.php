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
     * 🔥 INDEX — Route utama GET /api/jobs
     * Otomatis deteksi role (Tukang / Pelanggan)
     * =====================================================
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        $query = Job::with([
            'user.customerProfile',
            'service',
            'category',
            'tukangProfile',
        ]);

        if ($user->hasRole('Tukang')) {
            // Tukang: ambil job yg ditugaskan ke dia
            $tukangProfileId = $user->tukangProfile?->id;

            if (!$tukangProfileId) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Profil tukang belum dibuat',
                ]);
            }

            $query->where('tukang_profile_id', $tukangProfileId);
        } else {
            // Pelanggan: ambil job miliknya
            $query->where('user_id', $user->id);
        }

        // Optional filter ?status=pending
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Optional filter ?type=active|history
        if ($request->type === 'active') {
            $query->whereIn('status', ['pending', 'diterima', 'dikerjakan']);
        } elseif ($request->type === 'history') {
            $query->whereIn('status', ['selesai', 'dibatalkan']);
        }

        $jobs = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $jobs->map(fn($job) => $this->formatJob($job)),
        ]);
    }

    /**
     * =====================================================
     * SHOW — Detail 1 job
     * =====================================================
     */
    public function show($id)
    {
        $job = Job::with(['user.customerProfile', 'service', 'category', 'tukangProfile'])
            ->find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatJob($job),
        ]);
    }

    /**
     * =====================================================
     * STORE — Pelanggan bikin pesanan
     * =====================================================
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user->hasRole('Pelanggan')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pelanggan yang bisa membuat job',
            ], 403);
        }

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'deskripsi'  => 'required|string',
            'price'      => 'required|integer|min:0',
            'alamat'     => 'nullable|string',
        ]);

        $service = \App\Models\Service::with('category')->findOrFail($request->service_id);

        $job = Job::create([
            'user_id'     => $user->id,
            'service_id'  => $service->id,
            'category_id' => $service->category_id,
            'deskripsi'   => $request->deskripsi,
            'price'       => $request->price,
            'alamat'      => $request->alamat ?? $user->customerProfile?->alamat,
            'status'      => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil dibuat',
            'data'    => $this->formatJob(
                $job->load(['service', 'category', 'user.customerProfile'])
            ),
        ], 201);
    }

    /**
     * =====================================================
     * UPDATE — Edit pesanan
     * =====================================================
     */
    public function update(Request $request, $id)
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job tidak ditemukan',
            ], 404);
        }

        if ($job->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak diizinkan',
            ], 403);
        }

        $job->update($request->only(['deskripsi', 'price', 'alamat']));

        return response()->json([
            'success' => true,
            'message' => 'Job diperbarui',
            'data'    => $this->formatJob($job->load(['service', 'category', 'user.customerProfile'])),
        ]);
    }

    /**
     * =====================================================
     * DESTROY — Hapus pesanan
     * =====================================================
     */
    public function destroy($id)
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job tidak ditemukan',
            ], 404);
        }

        if ($job->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak diizinkan',
            ], 403);
        }

        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job dihapus',
        ]);
    }

    /**
     * =====================================================
     * JOB MILIK PELANGGAN (endpoint lama, masih dipakai?)
     * =====================================================
     */
    public function myJobs()
    {
        $user = Auth::guard('api')->user();

        $jobs = Job::with(['service', 'category', 'tukangProfile', 'user.customerProfile'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $jobs->map(fn($job) => $this->formatJob($job)),
        ]);
    }

    /**
     * =====================================================
     * JOB TERSEDIA BUAT TUKANG (status=pending)
     * =====================================================
     */
    public function availableJobs()
    {
        $user = Auth::guard('api')->user();

        if (!$user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak',
            ], 403);
        }

        $jobs = Job::with(['user.customerProfile', 'service', 'category'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $jobs->map(fn($job) => $this->formatJob($job)),
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

        if (!$user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya tukang yang bisa menerima job',
            ], 403);
        }

        $job = Job::find($id);

        if (!$job || $job->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Job tidak tersedia',
            ], 400);
        }

        $job->update([
            'tukang_profile_id' => $user->tukangProfile->id,
            'status' => 'diterima',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil diterima',
            'data' => $this->formatJob($job->load(['user.customerProfile', 'service', 'category'])),
        ]);
    }

    /**
     * =====================================================
     * UPDATE STATUS (🔥 Dipakai tombol Terima/Kerjakan/Selesai)
     * =====================================================
     */
    public function updateStatus(Request $request, $id)
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:pending,diterima,dikerjakan,selesai,dibatalkan',
        ]);

        $user = Auth::guard('api')->user();

        // 🔥 Kalau status diterima & job belum punya tukang, assign dia
        if ($request->status === 'diterima' && !$job->tukang_profile_id) {
            if ($user->hasRole('Tukang') && $user->tukangProfile) {
                $job->tukang_profile_id = $user->tukangProfile->id;
            }
        }

        $job->status = $request->status;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui',
            'data' => $this->formatJob(
                $job->load(['user.customerProfile', 'service', 'category', 'tukangProfile'])
            ),
        ]);
    }

    /**
     * =====================================================
     * 🔥 FORMAT RESPONSE — Disamakan sama yang dibaca Flutter
     * =====================================================
     */
    private function formatJob($job)
    {
        return [
            'id'         => $job->id,
            'deskripsi'  => $job->deskripsi,                 // ✅ Flutter baca ini
            'status'     => $job->status,
            'price'      => (int) $job->price,
            'alamat'     => $job->alamat
                            ?? $job->user?->customerProfile?->alamat
                            ?? null,                         // ✅ fallback ke profile
            'created_at' => $job->created_at,

            // Nested objects (Flutter baca `category.nama` / `service.nama`)
            'category' => $job->category ? [
                'id'   => $job->category->id,
                'nama' => $job->category->nama ?? $job->category->name ?? '-',
            ] : null,

            'service' => $job->service ? [
                'id'   => $job->service->id,
                'nama' => $job->service->nama ?? $job->service->name ?? '-',
            ] : null,

            'user' => $job->user ? [
                'id'    => $job->user->id,
                'name'  => $job->user->name,
                'phone' => $job->user->phone ?? $job->user->customerProfile?->phone ?? null,
            ] : null,
        ];
    }
}