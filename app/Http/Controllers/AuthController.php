<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TukangProfile;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * ============================
     * REGISTER - AUTO CREATE PROFILE
     * ============================
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:pelanggan,tukang',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->messages()
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign role
        $user->assignRole($request->role);

        // ✅ AUTO CREATE PROFILE BERDASARKAN ROLE
        if ($request->role === 'pelanggan') {
            CustomerProfile::create([
                'user_id' => $user->id,
                'alamat' => null,
                'no_telepon' => null,
            ]);
        } else {
            // TUKANG
            TukangProfile::create([
                'user_id' => $user->id,
                'foto' => 'no_image.jpg',
                'deskripsi' => null,
                'no_hp' => null,
                'kota' => null,
                'rating' => 5.00,
                'is_active' => true,
            ]);
        }

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    /**
     * ============================
     * LOGIN - AUTO CREATE TUKANG PROFILE
     * ============================
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        $user = auth('api')->user();
        
        // ✅ AUTO CREATE tukang_profiles jika tukang & belum ada
        if ($user->getRoleNames()->first() === 'tukang') {
            TukangProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'foto' => 'no_image.jpg',
                    'deskripsi' => null,
                    'no_hp' => null,
                    'kota' => null,
                    'rating' => 5.00,
                    'is_active' => true,
                ]
            );
        }

        return $this->respondWithToken($token);
    }

    /**
     * ============================
     * ME - UNIFIED PROFILE RESPONSE
     * ============================
     */
    public function me()
    {
        $user = auth('api')->user();
        $role = $user->getRoleNames()->first();

        $profileData = [];

        if ($role === 'pelanggan') {
            $profile = $user->customerProfile;
            $profileData = [
                'type' => 'pelanggan',
                'alamat' => $profile->alamat,
                'no_telepon' => $profile->no_telepon,
            ];
        } else {
            // TUKANG
            $profile = $user->tukangProfile;
            $profileData = [
                'type' => 'tukang',
                'foto' => $profile->foto,
                'deskripsi' => $profile->deskripsi,
                'no_hp' => $profile->no_hp,
                'kota' => $profile->kota,
                'rating' => $profile->rating,
                'is_active' => $profile->is_active,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $role,
                'profile'  => $profileData,
            ]
        ]);
    }

    /**
     * ============================
     * UPDATE PROFILE - HANDLE SEMUA ROLE
     * ============================
     */
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();
        $role = $user->getRoleNames()->first();

        $request->validate([
            'name'     => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
        ]);

        // Update user basic info
        $userData = [
            'name' => $request->name ?? $user->name,
            'username' => $request->username ?? $user->username,
            'email' => $request->email ?? $user->email,
        ];

        if ($request->password) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Update profile berdasarkan role
        if ($role === 'pelanggan') {
            $user->customerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'alamat' => $request->alamat,
                    'no_telepon' => $request->no_telepon,
                ]
            );
        } else {
            // TUKANG
            $user->tukangProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'no_hp' => $request->no_hp,
                    'kota' => $request->kota,
                    'deskripsi' => $request->deskripsi,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diperbarui',
            'data' => $user->load(['customerProfile', 'tukangProfile'])
        ]);
    }

    /**
     * ============================
     * LOGOUT
     * ============================
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout'
        ]);
    }

    /**
     * ============================
     * REFRESH TOKEN
     * ============================
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * ============================
     * TOKEN RESPONSE
     * ============================
     */
    protected function respondWithToken($token)
    {
        $user = auth('api')->user();
        $role = $user->getRoleNames()->first();

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $role,
            ]
        ]);
    }
}