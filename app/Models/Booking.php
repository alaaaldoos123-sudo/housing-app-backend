<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'apartment_id',
        'check_in',
        'check_out',
        'total_price',
        'status',
        'notes',

    ];

    protected $casts = [
        'check_in'    => 'date:Y-m-d',
        'check_out'   => 'date:Y-m-d',
        'total_price' => 'double',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }


     public function scopeForOwner(Builder $query, $ownerId)
    {
        return $query->whereHas('apartment', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });
    }
  public function scopePending(Builder $query)
    {
        return $query->where('status', 'pending');
    }
}
