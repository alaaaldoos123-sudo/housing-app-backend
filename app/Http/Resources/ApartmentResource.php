<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            // 👇 1. الحقول الديناميكية (للعرض حسب لغة الموبايل)
            'name' => $this->name,
            'description' => $this->description,
            'location' => $this->location,
            'city' => $this->city,
            'province' => $this->province,

            // 👇 2. الحقول الخام (ضرورية عشان شاشة التعديل تقدر تعبي الخانات)
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,

            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,

            'location_ar' => $this->location_ar,
            'location_en' => $this->location_en,

            'city_ar' => $this->city_ar,
            'city_en' => $this->city_en,

            'province_ar' => $this->province_ar,
            'province_en' => $this->province_en,

            // الحقول المشتركة
            'price' => $this->price,
            'price_unit' => $this->price_unit,

            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,

            'image_url' => $this->image_url ? url('storage/' . $this->image_url) : null,

            'image_urls' => collect($this->image_urls)->map(function($path) {
                return url('storage/' . $path);
            }),

            'amenities' => $this->amenities,
            'rating' => (double) $this->rating,
            'review_count' => (int) $this->review_count,

            'status' => $this->status,
            'is_published' => (bool) $this->is_published,
            'is_favorite' => $this->is_favorite, // مفيد جداً للفرونت

            'owner' => $this->owner,

            // بيانات الحجز النشط (للمالك)
            'current_booking' => $this->activeBooking ? [
                'id' => $this->activeBooking->id,
                'check_in' => $this->activeBooking->check_in,
                'check_out' => $this->activeBooking->check_out,
                'total_price' => (double) $this->activeBooking->total_price,
                'status' => $this->activeBooking->status,
                'user' => [
                    'first_name' => $this->activeBooking->user->first_name,
                    'last_name' => $this->activeBooking->user->last_name,
                    'profile_image' => $this->activeBooking->user->profile_image,
                ]
            ] : null,
        ];
    }
}
