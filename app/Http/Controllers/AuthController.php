<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * ============================
     * REGISTER
     * ============================
     */
    public function register()
    {
        $validator = \Validator::make(request()->all(), [
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:pelanggan,tukang',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->messages()
            ], 422);
        }

        $user = User::create([
            'name'     => request('name'),
            'username' => request('username'),
            'email'    => request('email'),
            'password' => Hash::make(request('password')),
        ]);

        // assign role
        $user->assignRole(request('role'));

        // buat profile kosong
        $user->customerProfile()->create([
            'alamat' => null,
            'no_telepon' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil',
            'user'    => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => request('role'),
            ]
        ], 201);
    }

    /**
     * ============================
     * LOGIN
     * ============================
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * ============================
     * ME
     * ============================
     */
    public function me()
    {
        $user = auth('api')->user();

        return response()->json([
            'id'       => $user->id,
            'name'     => $user->name,
            'username' => $user->username,
            'email'    => $user->email,
            'role'     => $user->getRoleNames()->first(),
            'alamat'   => $user->customerProfile->alamat ?? null,
            'no_telepon' => $user->customerProfile->no_telepon ?? null,
        ]);
    }

    /**
     * ============================
     * UPDATE PROFILE
     * ============================
     */
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'name'     => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'alamat'   => 'nullable|string',
            'no_telepon' => 'nullable|string',
        ]);

        $user->update([
            'name'     => $request->name ?? $user->name,
            'username' => $request->username ?? $user->username,
            'email'    => $request->email ?? $user->email,
        ]);

        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        $user->customerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'alamat' => $request->alamat,
                'no_telepon' => $request->no_telepon,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diperbarui',
            'data' => $user->load('customerProfile')
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
                'role'     => $user->getRoleNames()->first(),
                'alamat'   => $user->customerProfile->alamat ?? null,
                'no_telepon' => $user->customerProfile->no_telepon ?? null,
            ]
        ]);
    }
}
