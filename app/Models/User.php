<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Board;
use App\Models\TukangProfile;
use App\Models\Job;

class User extends Authenticatable implements MustVerifyEmail, JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * ============================
     * MASS ASSIGNMENT
     * ============================
     * 
     * $guarded digunakan agar semua field boleh diisi
     * kecuali field 'id'.
     * 
     * Cocok dipakai saat register user + assign role.
     */

    // protected $fillable = [
    //     'username',
    //     'name',
    //     'email',
    //     'password',
    // ];

    protected $guarded = ['id'];

    /**
     * ============================
     * HIDDEN ATTRIBUTES
     * ============================
     * 
     * Field yang tidak akan ikut
     * saat response JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * ============================
     * ATTRIBUTE CASTING
     * ============================
     * 
     * email_verified_at otomatis
     * di-cast ke datetime
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * ============================
     * RELATIONSHIP
     * ============================
     * 
     * User bisa punya banyak board
     * dengan role berbeda di pivot
     */
    public function boards()
    {
        return $this->belongsToMany(Board::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * ============================
     * TUKANGKU RELATIONSHIP
     * ============================
     */

    /**
     * Jika user adalah Tukang,
     * maka dia punya 1 TukangProfile
     */
    public function tukangProfile()
    {
        return $this->hasOne(TukangProfile::class);
    }

    /**
     * Jika user adalah Pelanggan,
     * maka dia bisa punya banyak Job
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    /**
     * ============================
     * JWT AUTH
     * ============================
     * 
     * Digunakan oleh tymon/jwt-auth
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
