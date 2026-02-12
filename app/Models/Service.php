<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function tukangProfile()
    {
        return $this->belongsTo(TukangProfile::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
