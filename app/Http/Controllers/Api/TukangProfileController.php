<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TukangProfile;
use App\Models\Job; // 🔥 TAMBAH INI

class TukangProfileController extends Controller
{
    /**
     * Ambil data profil tukang yang sedang login
     */
    public function show()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $profile = TukangProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profil tukang belum dibuat'
            ], 404);
        }

        return response()->json([
            'message' => 'Berhasil mengambil profil',
            'data' => $profile
        ]);
    }

    /**
     * Buat / update profil tukang
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->validate([
            'deskripsi' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
            'kota' => 'nullable|string|max:100',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $profile = TukangProfile::where('user_id', $user->id)->first();

        // 🔥 HANDLE FOTO
        $filename = $profile->foto ?? 'no_image.jpg';

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/tukang', $filename);
        }

        if (!$profile) {
            $profile = TukangProfile::create([
                'user_id' => $user->id,
                'deskripsi' => $request->deskripsi,
                'no_hp' => $request->no_hp,
                'kota' => $request->kota,
                'foto' => $filename,
            ]);
        } else {
            $profile->update([
                'deskripsi' => $request->deskripsi ?? $profile->deskripsi,
                'no_hp' => $request->no_hp ?? $profile->no_hp,
                'kota' => $request->kota ?? $profile->kota,
                'foto' => $filename,
            ]);
        }

        return response()->json([
            'message' => 'Profil berhasil disimpan',
            'data' => $profile
        ]);
    }

    /**
     * =========================
     * DASHBOARD 🔥
     * =========================
     */
    public function dashboard()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $profile = TukangProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profil tukang belum dibuat'
            ], 404);
        }

        // 🔥 HITUNG DATA JOB
        $total = Job::where('tukang_profile_id', $profile->id)->count();

        $pending = Job::where('tukang_profile_id', $profile->id)
            ->where('status', 'pending')
            ->count();

        $completed = Job::where('tukang_profile_id', $profile->id)
            ->where('status', 'selesai')
            ->count();

        return response()->json([
            'message' => 'Berhasil mengambil dashboard',
            'data' => [
                'total_jobs' => $total,
                'pending_jobs' => $pending,
                'completed_jobs' => $completed,
                'rating' => $profile->rating,
            ]
        ]);
    }
}