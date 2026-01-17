<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name_en', 'name_ar',
        'description_en', 'description_ar',
        'location_en', 'location_ar',
        'city_en', 'city_ar',
        'province_en', 'province_ar',
        'price', 'price_unit', 'bedrooms', 'bathrooms', 'area',
        'image_url', 'image_urls',
        'amenities',
        'rating', 'review_count', 'is_published', 'status',
    ];

    protected $casts = [
        'image_urls'   => 'array',
        'amenities'    => 'array',
        'price'        => 'double',
        'rating'       => 'double',
        'is_published' => 'boolean',
    ];

    protected $appends = ['is_favorite', 'name', 'description', 'location', 'city', 'province'];

    public static $amenitiesList = [
        'wifi'          => ['ar' => 'واي فاي',      'en' => 'WiFi'],
        'ac'            => ['ar' => 'تكييف',        'en' => 'Air Conditioning'],
        'elevator'      => ['ar' => 'مصعد',         'en' => 'Elevator'],
        'parking'       => ['ar' => 'باركينغ',      'en' => 'Parking'],
        'pool'          => ['ar' => 'مسبح',         'en' => 'Swimming Pool'],
        'solar_energy'  => ['ar' => 'طاقة شمسية',   'en' => 'Solar Energy'],
        'furnished'     => ['ar' => 'فرش كامل',     'en' => 'Fully Furnished'],
        'balcony'       => ['ar' => 'بلكونة',       'en' => 'Balcony'],
        'heating'       => ['ar' => 'تدفئة',        'en' => 'Heating'],
        'security'      => ['ar' => 'حراسة',        'en' => 'Security'],
    ];
    public function getNameAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->name_ar)) ? $this->name_ar : $this->name_en;
    }

    public function getDescriptionAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->description_ar)) ? $this->description_ar : $this->description_en;
    }

    public function getLocationAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->location_ar)) ? $this->location_ar : $this->location_en;
    }

    public function getCityAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->city_ar)) ? $this->city_ar : $this->city_en;
    }

    public function getProvinceAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->province_ar)) ? $this->province_ar : $this->province_en;
    }
public function getIsFavoriteAttribute()
    {
        if (!Auth::guard('sanctum')->check()) {
            return false;
        }
        return $this->favorites()
            ->where('user_id', Auth::guard('sanctum')->id())
            ->exists();
    }

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function favorites() { return $this->hasMany(Favorite::class); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function reviews() { return $this->hasMany(Review::class); }

    public function activeBooking()
    {
        return $this->hasOne(Booking::class)
            ->where('status', 'accepted')
            ->whereDate('check_out', '>=', now())
            ->latest();
    }
}
