<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tukang_profile_id',
        'service_id',
        'category_id',
        'deskripsi',
        'price',
        'alamat', // 🔥 WAJIB (biar bisa disimpan)
        'status'
    ];

    /**
     * =========================
     * RELATIONSHIPS
     * =========================
     */

    // 🔹 Pelanggan
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔹 Tukang (via profile)
    public function tukangProfile()
    {
        return $this->belongsTo(TukangProfile::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}