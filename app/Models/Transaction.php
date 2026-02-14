<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'tukang_profile_id',
        'amount',
        'status'
    ];

    protected $casts = [
        'amount' => 'integer',
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
