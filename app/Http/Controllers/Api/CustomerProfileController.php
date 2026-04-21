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
     * LIHAT PROFIL LENGKAP
     * GET /api/customer/profile
     * ==============================
     */
    public function show()
    {
        $user = Auth::guard('api')->user();
        $profile = CustomerProfile::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'foto' => $user->foto,
                'alamat' => $profile?->alamat ?? null,
                'phone' => $profile?->no_telepon ?? null,
            ]
        ]);
    }

    /**
     * ==============================
     * UPDATE PROFIL LENGKAP
     * POST /api/customer/profile
     * ==============================
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string',
        ]);

        // Update users table (name, email)
        if ($request->filled('name') || $request->filled('email')) {
            $user->update([
                'name' => $request->name ?? $user->name,
                'email' => $request->email ?? $user->email,
            ]);
        }

        // Update customer_profiles table
        $profile = CustomerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'alamat' => $request->alamat,
                'no_telepon' => $request->no_telepon,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil disimpan',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'foto' => $user->foto,
                'alamat' => $profile->alamat,
                'phone' => $profile->no_telepon,
            ]
        ]);
    }
}