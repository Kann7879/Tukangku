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
            'service.category',        // 🔥 FIX: load category via service
            'tukangProfile.user',      // 🔥 FIX: load user tukang
        ]);

        if ($user->hasRole('Tukang')) {
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
            $query->where('user_id', $user->id);
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter type
        if ($request->type === 'active') {
            $query->whereIn('status', ['pending', 'diterima', 'dikerjakan']);
        } elseif ($request->type === 'history') {
            $query->whereIn('status', ['selesai', 'dibatalkan']);
        }

        $jobs = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $jobs->map(fn($job) => $this->formatJob($job))->toArray(),
        ]);
    }

    /**
     * =====================================================
     * SHOW — Detail 1 job
     * =====================================================
     */
    public function show($id)
    {
        $job = Job::with([
            'user.customerProfile', 
            'service.category',
            'tukangProfile.user',
        ])->findOrFail($id); // 🔥 FAIL → 404 auto

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
            'deskripsi'  => 'required|string|max:1000',
            'price'      => 'required|integer|min:10000|max:50000000', // Realistic range
            'alamat'     => 'required|string|max:500', // 🔥 WAJIB!
        ]);

        $service = \App\Models\Service::with('category')->findOrFail($request->service_id);

        $job = Job::create([
            'user_id'        => $user->id,
            'service_id'     => $service->id,
            'category_id'    => $service->category_id,
            'deskripsi'      => $request->deskripsi,
            'price'          => $request->price,
            'alamat'         => $request->alamat,
            'status'         => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil dibuat!',
            'data'    => $this->formatJob(
                $job->load(['service.category', 'tukangProfile.user'])
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
        $job = Job::findOrFail($id); // 🔥 FAIL → 404

        if ($job->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak diizinkan edit job orang lain',
            ], 403);
        }

        $request->validate([
            'deskripsi' => 'sometimes|string|max:1000',
            'price'     => 'sometimes|integer|min:10000|max:50000000',
            'alamat'    => 'sometimes|string|max:500',
        ]);

        $job->update($request->only(['deskripsi', 'price', 'alamat']));

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil diupdate',
            'data'    => $this->formatJob(
                $job->load(['service.category', 'tukangProfile.user'])
            ),
        ]);
    }

    /**
     * =====================================================
     * DESTROY — Hapus pesanan (hanya pending)
     * =====================================================
     */
    public function destroy($id)
    {
        $job = Job::findOrFail($id);

        if ($job->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak diizinkan hapus job orang lain',
            ], 403);
        }

        if (!in_array($job->status, ['pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya job pending yang bisa dihapus',
            ], 400);
        }

        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil dihapus',
        ]);
    }

    /**
     * =====================================================
     * TERIMA JOB (Tukang)
     * =====================================================
     */
    public function acceptJob($id)
    {
        $user = Auth::guard('api')->user();
        $tukangProfile = $user->tukangProfile;

        if (!$user->hasRole('Tukang') || !$tukangProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil tukang belum lengkap',
            ], 403);
        }

        $job = Job::findOrFail($id);

        if ($job->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Job sudah diambil orang lain',
            ], 400);
        }

        $job->update([
            'tukang_profile_id' => $tukangProfile->id,
            'status' => 'diterima',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil diterima!',
            'data' => $this->formatJob($job->load(['service.category', 'user.customerProfile'])),
        ]);
    }

    /**
     * =====================================================
     * UPDATE STATUS (Tombol: Kerjakan/Selesai/Batal)
     * =====================================================
     */
    public function updateStatus(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $user = Auth::guard('api')->user();

        $request->validate([
            'status' => 'required|in:diterima,dikerjakan,selesai,dibatalkan',
        ]);

        // 🔥 AUTHORIZATION
        match($request->status) {
            'diterima', 'dikerjakan', 'selesai' => function() use ($user, $job) {
                if (!$user->hasRole('Tukang') || $job->tukang_profile_id !== $user->tukangProfile?->id) {
                    throw new \Exception('Hanya tukang yang mengerjakan job ini');
                }
            },
            'dibatalkan' => function() use ($user, $job) {
                if ($job->user_id !== $user->id) {
                    throw new \Exception('Hanya pemilik job yang bisa membatalkan');
                }
            },
            default => null,
        };

        $job->status = $request->status;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => "Status diupdate ke '{$request->status}'",
            'data' => $this->formatJob(
                $job->load(['service.category', 'tukangProfile.user', 'user.customerProfile'])
            ),
        ]);
    }

    /**
     * =====================================================
     * 🔥 FORMAT RESPONSE — NULL-SAFE & Konsisten
     * =====================================================
     */
    private function formatJob($job)
    {
        return [
            'id'         => (int) $job->id,
            'deskripsi'  => $job->deskripsi ?? '',
            'status'     => $job->status ?? 'pending',
            'price'      => (int) ($job->price ?? 0),
            'alamat'     => $job->alamat ?? '',
            'created_at' => $job->created_at?->format('d M Y H:i') ?? '',

            // 🔥 SERVICE & CATEGORY
            'service' => $job->service ? [
                'id'            => (int) $job->service->id,
                'category_id'   => (int) ($job->service->category_id ?? 0),
                'category_name' => $job->service->category->name ?? 'Layanan Umum',
                'price_min'     => (int) ($job->service->price_min ?? 0),
                'price_max'     => (int) ($job->service->price_max ?? 0),
                'price_range'   => $this->formatPriceRange($job->service),
                'deskripsi'     => $job->service->deskripsi ?? '',
            ] : null,

            // 🔥 USER (Pelanggan)
            'user' => $job->user ? [
                'id'       => (int) $job->user->id,
                'name'     => $job->user->name ?? '',
                'username' => $job->user->username ?? '',
                'phone'    => $job->user->phone ?? $job->user->customerProfile?->phone ?? '',
            ] : null,

            // 🔥 TUKANG
            'tukang' => $job->tukangProfile ? [
                'id'       => (int) $job->tukangProfile->id,
                'name'     => $job->tukangProfile->user->name ?? '',
                'foto'     => $job->tukangProfile->foto,
                'phone'    => $job->tukangProfile->no_hp ?? '',
                'rating'   => (float) ($job->tukangProfile->rating ?? 0),
            ] : null,
        ];
    }

    /**
     * 🔥 Helper format harga range
     */
    private function formatPriceRange($service)
    {
        $min = (int) ($service->price_min ?? 0);
        $max = (int) ($service->price_max ?? 0);
        return 'Rp ' . number_format($min) . ' - Rp ' . number_format($max);
    }
}