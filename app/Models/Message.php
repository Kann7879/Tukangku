<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'sender_id',
        'message'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}