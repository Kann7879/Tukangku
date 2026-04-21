<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TukangProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'foto',
        'deskripsi',
        'no_hp',
        'kota',
        'rating',
        'is_active'
    ];

    protected $casts = [
        'id' => 'integer',        // ✅ pastikan integer
        'user_id' => 'integer',   // ✅ pastikan integer
        'rating' => 'float',      // ✅ float (bisa desimal)
        'is_active' => 'boolean', // ✅ boolean
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getFotoAttribute($value)
    {
        if (!$value || $value === 'no_image.jpg') {
            return asset('storage/tukang/no_image.jpg');
        }
        return asset('storage/tukang/' . $value);
    }
}