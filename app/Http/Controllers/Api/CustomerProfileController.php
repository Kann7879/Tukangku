<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\Auth;

class CustomerProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * ==============================
     * LIHAT PROFIL
     * GET /api/customer/profile
     * ==============================
     */
    public function show()
    {
        $user = Auth::guard('api')->user();

        $profile = CustomerProfile::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    /**
     * ==============================
     * BUAT / UPDATE PROFIL
     * POST /api/customer/profile
     * ==============================
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        $request->validate([
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string'
        ]);

        $profile = CustomerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'alamat' => $request->alamat,
                'no_telepon' => $request->no_telepon
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil disimpan',
            'data' => $profile
        ]);
    }
}