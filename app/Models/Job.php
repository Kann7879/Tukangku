<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class); // pelanggan
    }

    public function tukangProfile()
    {
        return $this->belongsTo(TukangProfile::class);
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
}
