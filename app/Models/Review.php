<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'tukang_profile_id',
        'rating',
        'comment'
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function tukangProfile()
    {
        return $this->belongsTo(TukangProfile::class);
    }
}
