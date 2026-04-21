<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TukangProfile;
use App\Models\Job;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TukangProfileController extends Controller
{
    /**
     * GET /api/tukang/profile
     * Ambil profil tukang yang sedang login
     */
    public function show()
    {
        $user = Auth::user();

        $profile = TukangProfile::where('user_id', $user->id)->first();

        // Kalau belum punya profile, buat kosong dulu
        if (!$profile) {
            $profile = TukangProfile::create([
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id'       => (int) $user->id,
                    'name'     => $user->name,
                    'username' => $user->username,
                    'email'    => $user->email,
                ],
                'profile' => [
                    'foto'      => $profile->foto,
                    'no_hp'     => $profile->no_hp,
                    'kota'      => $profile->kota,
                    'deskripsi' => $profile->deskripsi,
                    'rating'    => (float) ($profile->rating ?? 0),
                    'is_active' => (bool) $profile->is_active,
                ],
            ]
        ]);
    }

    /**
     * POST /api/tukang/profile
     * Update profil tukang
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $profile = TukangProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            $profile = TukangProfile::create([
                'user_id'   => $user->id,
                'is_active' => true,
            ]);
        }

        $profile->update([
            'no_hp'     => $request->no_hp,
            'kota'      => $request->kota,
            'deskripsi' => $request->deskripsi,
        ]);

        // Update nama user juga kalau dikirim
        if ($request->name) {
            $user->update(['name' => $request->name]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
        ]);
    }

    /**
     * POST /api/tukang/profile/photo
     * Upload foto profil tukang
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate(['foto' => 'required|image|max:2048']);

        $user    = Auth::user();
        $profile = TukangProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profil tidak ditemukan'], 404);
        }

        // Hapus foto lama kalau ada
        if ($profile->getOriginal('foto') && $profile->getOriginal('foto') !== 'no_image.jpg') {
            Storage::disk('public')->delete('tukang/' . $profile->getOriginal('foto'));
        }

        $filename = time() . '_' . $user->id . '.' . $request->file('foto')->extension();
        $request->file('foto')->storeAs('tukang', $filename, 'public');

        $profile->update(['foto' => $filename]);

        return response()->json([
            'success'  => true,
            'message'  => 'Foto berhasil diupload',
            'foto_url' => asset('storage/tukang/' . $filename),
        ]);
    }

    /**
     * GET /api/tukang/dashboard
     */
    public function dashboard()
    {
        $user    = Auth::user();
        $profile = TukangProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_jobs'     => 0,
                    'pending_jobs'   => 0,
                    'completed_jobs' => 0,
                    'rating'         => 0.0,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_jobs'     => (int) Job::where('tukang_profile_id', $profile->id)->count(),
                'pending_jobs'   => (int) Job::where('tukang_profile_id', $profile->id)->where('status', 'pending')->count(),
                'completed_jobs' => (int) Job::where('tukang_profile_id', $profile->id)->where('status', 'completed')->count(),
                'rating'         => (float) ($profile->rating ?? 0),
            ]
        ]);
    }

    // ============================================================
    // PUBLIC METHODS
    // ============================================================

    /** GET /api/tukangs */
    /** GET /api/tukangs - DASHBOARD (FIXED!) */
    public function index()
    {
        $tukangs = TukangProfile::with(['user', 'services.category']) // 🔥 ADD!
            ->where('is_active', true)
            ->withCount(['jobs as total_jobs'])
            ->orderBy('rating', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tukangs->map(function ($profile) {
                return [
                    'id'             => (int) $profile->id,
                    'name'           => $profile->user->name,
                    'username'       => $profile->user->username,
                    'foto'           => $profile->foto,
                    'kota'           => $profile->kota,
                    'deskripsi'      => $profile->deskripsi,
                    'rating'         => (float) ($profile->rating ?? 0),
                    'total_reviews'  => (int) ($profile->total_reviews ?? 0), // Tambah
                    'total_jobs'     => (int) $profile->total_jobs,
                    'is_active'      => (bool) $profile->is_active,
                    // 🔥 SERVICES + HARGA!
                    'services' => $profile->services->map(function ($service) {
                        return [
                            'id'            => (int) $service->id,
                            'category_id'   => (int) $service->category_id,
                            'category_name' => $service->category->name ?? 'Layanan',
                            'price_min'     => (int) $service->price_min,  // 🔥 50000
                            'price_max'     => (int) $service->price_max,  // 🔥 150000
                            'deskripsi'     => $service->deskripsi,
                        ];
                    }),
                ];
            })
        ]);
    }

    /** GET /api/tukang/top - TOP TUKANG (FIXED!) */
    public function top()
    {
        $tukangs = TukangProfile::with(['user', 'services.category']) // 🔥 ADD!
            ->where('is_active', true)
            ->withCount(['jobs as total_jobs'])
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tukangs->map(function ($profile) {
                return [
                    'id'             => (int) $profile->id,
                    'name'           => $profile->user->name,
                    'foto'           => $profile->foto,
                    'kota'           => $profile->kota,
                    'rating'         => (float) ($profile->rating ?? 0),
                    'total_reviews'  => (int) ($profile->total_reviews ?? 0),
                    'total_jobs'     => (int) $profile->total_jobs,
                    // 🔥 SERVICES!
                    'services' => $profile->services->take(3)->map(function ($service) {
                        return [
                            'id'            => (int) $service->id,
                            'category_id'   => (int) $service->category_id,
                            'category_name' => $service->category->name ?? 'Layanan',
                            'price_min'     => (int) $service->price_min,
                            'price_max'     => (int) $service->price_max,
                            'deskripsi'     => $service->deskripsi,
                        ];
                    }),
                ];
            })
        ]);
    }

    /** GET /api/tukangs/category/{categoryId} (FIXED!) */
    public function byCategory($categoryId)
    {
        $tukangs = TukangProfile::with(['user', 'services.category']) // 🔥 ADD!
            ->where('is_active', true)
            ->whereHas('services', function ($q) use ($categoryId) {
                $q->where('category_id', (int) $categoryId);
            })
            ->withCount(['jobs as total_jobs'])
            ->orderBy('rating', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tukangs->map(function ($profile) {
                return [
                    'id'             => (int) $profile->id,
                    'name'           => $profile->user->name,
                    'foto'           => $profile->foto,
                    'kota'           => $profile->kota,
                    'rating'         => (float) ($profile->rating ?? 0),
                    'total_reviews'  => (int) ($profile->total_reviews ?? 0),
                    'total_jobs'     => (int) $profile->total_jobs,
                    // 🔥 SERVICES!
                    'services' => $profile->services->map(function ($service) {
                        return [
                            'id'            => (int) $service->id,
                            'category_id'   => (int) $service->category_id,
                            'category_name' => $service->category->name ?? 'Layanan',
                            'price_min'     => (int) $service->price_min,
                            'price_max'     => (int) $service->price_max,
                            'deskripsi'     => $service->deskripsi,
                        ];
                    }),
                ];
            })
        ]);
    }

    /** GET /api/tukang/{id} */
    public function showPublic($id)
    {
        $profile = TukangProfile::with(['user', 'services.category'])
            ->where('id', (int) $id)
            ->where('is_active', true)
            ->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Tukang tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'         => (int) $profile->id,
                'name'       => $profile->user->name,
                'username'   => $profile->user->username,
                'foto'       => $profile->foto,
                'deskripsi'  => $profile->deskripsi,
                'no_hp'      => $profile->no_hp,
                'kota'       => $profile->kota,
                'rating'     => (float) $profile->rating,
                'total_jobs' => (int) Job::where('tukang_profile_id', $profile->id)->count(),
                'services'   => $profile->services->map(function ($service) {
                    return [
                        'id'        => (int) $service->id,
                        'name'      => $service->category->name ?? 'Layanan',
                        'price_min' => (int) $service->price_min,
                        'price_max' => (int) $service->price_max,
                        'deskripsi' => $service->deskripsi,
                    ];
                })
            ]
        ]);
    }
}