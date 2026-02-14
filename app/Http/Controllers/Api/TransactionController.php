<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * ==========================================
     * CONSTRUCTOR
     * ==========================================
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * ==========================================
     * BUAT TRANSAKSI (AUTO AMBIL HARGA DARI JOB)
     * ==========================================
     * POST /api/transactions
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        $request->validate([
            'job_id' => 'required|exists:jobs,id',
        ]);

        $job = Job::find($request->job_id);

        // Pastikan job valid & sudah diterima tukang
        if (!$job || $job->status !== 'diterima') {
            return response()->json([
                'success' => false,
                'message' => 'Job belum diterima atau tidak valid'
            ], 400);
        }

        // Pastikan hanya pemilik job (pelanggan) yang bisa buat transaksi
        if ($job->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak membuat transaksi ini'
            ], 403);
        }

        // Cegah transaksi ganda
        if ($job->transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi sudah dibuat'
            ], 400);
        }

        $transaction = Transaction::create([
            'job_id'            => $job->id,
            'tukang_profile_id' => $job->tukang_profile_id,
            'amount'            => $job->price, // 🔥 otomatis dari job
            'status'            => 'pending'
        ]);

        $transaction->load(['job', 'tukangProfile']);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat',
            'data'    => $transaction
        ], 201);
    }

    /**
     * ==========================================
     * LIST TRANSAKSI MILIK USER (PELANGGAN)
     * ==========================================
     * GET /api/transactions/my
     */
    public function myTransactions()
    {
        $user = Auth::guard('api')->user();

        $transactions = Transaction::with(['job', 'tukangProfile'])
            ->whereHas('job', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * ==========================================
     * BAYAR TRANSAKSI
     * ==========================================
     * PATCH /api/transactions/{id}/pay
     */
    public function pay($id)
    {
        $user = Auth::guard('api')->user();

        $transaction = Transaction::with('job')->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        // Pastikan hanya pemilik job yang bisa bayar
        if ($transaction->job->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak membayar transaksi ini'
            ], 403);
        }

        if ($transaction->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi sudah dibayar'
            ], 400);
        }

        // Update status transaksi
        $transaction->update([
            'status' => 'paid'
        ]);

        // Optional: otomatis selesaikan job
        $transaction->job->update([
            'status' => 'selesai'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil',
            'data' => $transaction
        ]);
    }
}
