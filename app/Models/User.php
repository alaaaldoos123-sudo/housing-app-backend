<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'user_role',
        'birth_date',
        'id_image',
        'profile_image',
        'password',
        'status',
        'is_2fa_enabled',
        'verification_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_2fa_enabled' => 'boolean',
    ];

    public function apartments()
    {
        return $this->hasMany(Apartment::class, 'owner_id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
