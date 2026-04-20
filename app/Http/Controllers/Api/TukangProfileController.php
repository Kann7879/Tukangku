<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TukangProfile;
use App\Models\Job;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TukangProfileController extends Controller
{
    /**
     * Show FULL profile (USER + TUKANG PROFILE)
     */
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $profile = TukangProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'foto' => 'no_image.jpg',
                'deskripsi' => 'Tukang profesional siap melayani Anda',
                'no_hp' => null,
                'kota' => null,
                'rating' => 5.00,
                'is_active' => true,
            ]
        );

        // 🔥 FULL DATA: USER + PROFILE
        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ],
            'profile' => [
                'id' => $profile->id,
                'foto' => $profile->foto,
                'deskripsi' => $profile->deskripsi,
                'no_hp' => $profile->no_hp,
                'kota' => $profile->kota,
                'rating' => $profile->rating,
                'is_active' => $profile->is_active,
            ]
        ];

        return response()->json([
            'message' => 'Profil lengkap berhasil dimuat',
            'data' => $data
        ]);
    }

    /**
     * Update profile
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'deskripsi' => 'nullable|string|max:500',
            'no_hp' => 'nullable|string|max:20',
            'kota' => 'nullable|string|max:100',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $profile = TukangProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['foto' => 'no_image.jpg', 'rating' => 5.00, 'is_active' => true]
        );

        if ($request->hasFile('foto')) {
            if ($profile->foto !== 'no_image.jpg' && Storage::disk('public')->exists($profile->foto)) {
                Storage::disk('public')->delete($profile->foto);
            }
            $filename = time() . '_' . $request->file('foto')->getClientOriginalName();
            $request->file('foto')->storeAs('public/tukang_profiles', $filename);
            $profile->foto = $filename;
        }

        $profile->update($request->only(['deskripsi', 'no_hp', 'kota']));

        return response()->json([
            'message' => 'Profil berhasil diupdate',
            'data' => $profile
        ]);
    }

    /**
     * Dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        $profile = TukangProfile::where('user_id', $user->id)->first();

        $stats = [
            'total_jobs' => Job::where('tukang_profile_id', $profile?->id ?? 0)->count(),
            'pending_jobs' => Job::where('tukang_profile_id', $profile?->id ?? 0)
                ->whereIn('status', ['pending', 'diterima'])->count(),
            'total_services' => Service::where('tukang_id', $user->id)->count(),
        ];

        return response()->json([
            'data' => array_merge(['profile' => $profile], $stats)
        ]);
    }
}