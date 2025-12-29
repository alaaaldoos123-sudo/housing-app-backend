<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'apartment_id',
    ];

     public function user()
    {
        return $this->belongsTo(User::class);
    }

      public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }
}
