<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TukangProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

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
}
