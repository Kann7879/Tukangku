<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (! $user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya tukang yang bisa menambahkan jasa'
            ], 403);
        }

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'price_min'   => 'required|integer|min:0',
            'price_max'   => 'required|integer|gte:price_min',
            'deskripsi'   => 'nullable|string'
        ]);

        $tukangProfile = $user->tukangProfile;

        if (! $tukangProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil tukang belum dibuat'
            ], 400);
        }

        $service = Service::create([
            'tukang_profile_id' => $tukangProfile->id,
            'category_id'       => $request->category_id,
            'price_min'         => $request->price_min,
            'price_max'         => $request->price_max,
            'deskripsi'         => $request->deskripsi,
        ]);

        $service->load(['category']);

        return response()->json([
            'success' => true,
            'message' => 'Jasa berhasil ditambahkan',
            'data'    => $service
        ], 201);
    }

    public function myServices()
    {
        $user = Auth::guard('api')->user();

        if (! $user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $tukangProfile = $user->tukangProfile;

        if (! $tukangProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil tukang belum dibuat'
            ], 404);
        }

        $services = Service::with('category')
            ->where('tukang_profile_id', $tukangProfile->id)
            ->get();

        $mappedServices = $services->map(function ($s) {
            return [
                'id'            => $s->id,
                'category_id'   => $s->category_id,
                'category_name' => $s->category ? $s->category->name : 'Umum',
                'price_min'     => $s->price_min,
                'price_max'     => $s->price_max,
                'deskripsi'     => $s->deskripsi,
                'created_at'    => $s->created_at ? $s->created_at->format('d M Y') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mappedServices
        ]);
    }
}