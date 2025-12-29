<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'city' => $this->city,
            'province' => $this->province,
            'price' => $this->price,
            'price_unit' => $this->price_unit,
            'description' => $this->description,
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

            // ✅✅✅ هذا هو التعديل الوحيد المطلوب
            'status' => $this->status,
            'is_published' => (bool) $this->is_published,

            'owner' => $this->owner,
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
