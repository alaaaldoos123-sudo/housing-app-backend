<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
     public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,

            'check_in' => $this->check_in->format('Y-m-d'),
            'check_out' => $this->check_out->format('Y-m-d'),

            'total_price' => (double) $this->total_price,
            'status' => $this->status,
            'notes' => $this->notes,


            'apartment' => new ApartmentResource($this->whenLoaded('apartment')),

            'owner_id' => (string) $this->apartment->owner_id,

            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
