<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * ==============================
     * KIRIM PESAN
     * POST /api/messages
     * ==============================
     */
    public function send(Request $request)
    {
        $user = Auth::guard('api')->user();

        $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'message' => 'required|string'
        ]);

        $job = Job::findOrFail($request->job_id);

        // chat hanya saat job aktif
        if ($job->status == 'selesai' || $job->status == 'dibatalkan') {
            return response()->json([
                'success' => false,
                'message' => 'Chat sudah ditutup'
            ], 400);
        }

        $message = Message::create([
            'job_id' => $job->id,
            'sender_id' => $user->id,
            'message' => $request->message
        ]);

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * ==============================
     * LIHAT CHAT
     * GET /api/messages/{job_id}
     * ==============================
     */
    public function getMessages($job_id)
    {
        $job = Job::findOrFail($job_id);

        if ($job->status == 'selesai' || $job->status == 'dibatalkan') {
            return response()->json([
                'success' => false,
                'message' => 'Chat sudah tidak tersedia'
            ], 400);
        }

        $messages = Message::with('sender')
            ->where('job_id', $job_id)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }
}