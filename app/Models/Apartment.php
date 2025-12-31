<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Apartment extends Model
{
    use HasFactory; // 👈 2. تأكد من إضافة هذا السطر داخل الكلاس
    protected $fillable = [
        'owner_id',

        // 👇 الحقول المترجمة (حسب الميغريشن الجديد)
        'name_en', 'name_ar',
        'description_en', 'description_ar',
        'location_en', 'location_ar',
        'city_en', 'city_ar',
        'province_en', 'province_ar',

        // الحقول المشتركة
        'price',
        'price_unit',
        'bedrooms',
        'bathrooms',
        'area',
        'image_url',
        'image_urls',
        'amenities',
        'rating',
        'review_count',
        'is_published',
        'status',
    ];

    protected $casts = [
        'image_urls'   => 'array',
        'amenities'    => 'array',
        'price'        => 'double',
        'rating'       => 'double',
        'is_published' => 'boolean',
    ];

    // 👇 أضفنا الحقول الوهمية هنا لترسل مع الـ JSON دائماً
    protected $appends = ['is_favorite', 'name', 'description', 'location', 'city', 'province'];

    // ==========================================
    // 🔥🔥 دوال الترجمة السحرية (Accessors) 🔥🔥
    // ==========================================

    // 1. الاسم
    public function getNameAttribute()
    {
        $locale = request()->header('Accept-Language');
        // إذا اللغة عربي والاسم العربي موجود رجعه، وإلا رجع الإنكليزي
        return ($locale == 'ar' && !empty($this->name_ar)) ? $this->name_ar : $this->name_en;
    }

    // 2. الوصف
    public function getDescriptionAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->description_ar)) ? $this->description_ar : $this->description_en;
    }

    // 3. الموقع التفصيلي
    public function getLocationAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->location_ar)) ? $this->location_ar : $this->location_en;
    }

    // 4. المدينة
    public function getCityAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->city_ar)) ? $this->city_ar : $this->city_en;
    }

    // 5. المحافظة
    public function getProvinceAttribute()
    {
        $locale = request()->header('Accept-Language');
        return ($locale == 'ar' && !empty($this->province_ar)) ? $this->province_ar : $this->province_en;
    }

    // ==========================================
    // العلاقات والمنطق القديم
    // ==========================================

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

    // 👇 ضرورية جداً لحساب التقييم
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function activeBooking()
    {
        return $this->hasOne(Booking::class)
            ->where('status', 'accepted')
            ->whereDate('check_out', '>=', now())
            ->latest();
    }
}
