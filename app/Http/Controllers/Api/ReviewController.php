<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Job;
use App\Models\TukangProfile;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * ==========================================
     * BUAT REVIEW
     * POST /api/reviews
     * ==========================================
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user->hasRole('Pelanggan')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pelanggan yang bisa memberi review'
            ], 403);
        }

        $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $job = Job::findOrFail($request->job_id);

        if ($job->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Job bukan milik anda'
            ], 403);
        }

        if ($job->status !== 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Job belum selesai'
            ], 400);
        }

        if ($job->review) {
            return response()->json([
                'success' => false,
                'message' => 'Review sudah dibuat'
            ], 400);
        }

        if (!$job->tukang_profile_id) {
            return response()->json([
                'success' => false,
                'message' => 'Tukang tidak valid'
            ], 400);
        }

        $review = Review::create([
            'job_id' => $job->id,
            'tukang_profile_id' => $job->tukang_profile_id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        /**
         * ==========================================
         * UPDATE RATING RATA-RATA TUKANG
         * ==========================================
         */

        $tukang = TukangProfile::find($job->tukang_profile_id);

        $averageRating = Review::where('tukang_profile_id', $tukang->id)
            ->avg('rating');

        $tukang->update([
            'rating' => round($averageRating, 2)
        ]);

        $review->load(['job', 'tukangProfile']);

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil dibuat',
            'data' => $review
        ], 201);
    }

    /**
     * ==========================================
     * LIST REVIEW UNTUK TUKANG
     * GET /api/reviews/tukang
     * ==========================================
     */
    public function myReviews()
    {
        $user = Auth::guard('api')->user();

        if (!$user->hasRole('Tukang')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $reviews = Review::with(['job'])
            ->where('tukang_profile_id', $user->tukangProfile->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }
}
