<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    /**
     * =====================================================
     * CONSTRUCTOR
     * =====================================================
     * 
     * Pastikan user sudah login (JWT)
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * =====================================================
     * TAMBAH JASA (KHUSUS TUKANG)
     * =====================================================
     * 
     * Endpoint:
     * POST /api/services
     * 
     * Body:
     * - category_id
     * - price_min
     * - price_max
     * - deskripsi (optional)
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        /**
         * =====================================================
         * VALIDASI ROLE
         * =====================================================
         * 
         * Pastikan hanya user dengan role "Tukang"
         */
        if (! $user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya tukang yang bisa menambahkan jasa'
            ], 403);
        }

        /**
         * =====================================================
         * VALIDASI DATA
         * =====================================================
         */
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'price_min'   => 'required|integer|min:0',
            'price_max'   => 'required|integer|gte:price_min',
            'deskripsi'   => 'nullable|string'
        ]);

        /**
         * =====================================================
         * AMBIL TUKANG PROFILE
         * =====================================================
         */
        $tukangProfile = $user->tukangProfile;

        if (! $tukangProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil tukang belum dibuat'
            ], 400);
        }

        /**
         * =====================================================
         * SIMPAN JASA
         * =====================================================
         */
        $service = Service::create([
            'tukang_profile_id' => $tukangProfile->id,
            'category_id'       => $request->category_id,
            'price_min'         => $request->price_min,
            'price_max'         => $request->price_max,
            'deskripsi'         => $request->deskripsi,
        ]);

        $service->load(['category']);

        /**
         * =====================================================
         * RESPONSE
         * =====================================================
         */
        return response()->json([
            'success' => true,
            'message' => 'Jasa berhasil ditambahkan',
            'data'    => $service
        ], 201);
    }

    /**
     * =====================================================
     * LIST JASA MILIK TUKANG (DASHBOARD)
     * =====================================================
     * 
     * Endpoint:
     * GET /api/services/my
     */
    public function myServices()
    {
        $user = Auth::guard('api')->user();

        if (! $user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $services = Service::with('category')
            ->where('tukang_profile_id', $user->tukangProfile->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }
}
