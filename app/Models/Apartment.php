<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Apartment extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'location',
        'city',
        'province',
        'price',
        'price_unit',
        'description',
        'bedrooms',
        'bathrooms',
        'area',
        'image_url',
        'image_urls',
        'amenities',
        'rating',
        'review_count',
        'is_published', // القديم (Boolean)
        'status',       // ✅ الجديد (String: pending, active, rejected)
    ];

    protected $casts = [
        'image_urls'   => 'array',
        'amenities'    => 'array',
        'price'        => 'double',
        'rating'       => 'double',
        'is_published' => 'boolean',
    ];

    // بقية الكود متل ما هو تماماً...
    protected $appends = ['is_favorite'];

    public function getIsFavoriteAttribute()
    {
        if (!Auth::guard('sanctum')->check()) {
            return false;
        }

        return $this->favorites()
            ->where('user_id', Auth::guard('sanctum')->id())
            ->exists();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function activeBooking()
    {
        return $this->hasOne(Booking::class)
            ->where('status', 'accepted')
            ->whereDate('check_out', '>=', now())
            ->latest();
    }
}
