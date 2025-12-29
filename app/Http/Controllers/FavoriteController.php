<?php

namespace App\Http\Controllers; // 1. تعديل الـ Namespace

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Apartment;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle($apartmentId)
    {
        $apartment = Apartment::find($apartmentId);
        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        $user = auth()->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('apartment_id', $apartmentId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json([
                'success' => true,
                'is_favorite' => false,
                'message' => 'Removed from favorites'
            ]);
        }

        Favorite::create([
            'user_id' => $user->id,
            'apartment_id' => $apartmentId
        ]);

        return response()->json([
            'success' => true,
            'is_favorite' => true,
            'message' => 'Added to favorites'
        ]);
    }
}
