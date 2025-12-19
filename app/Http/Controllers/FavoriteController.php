<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle($apartmentId)
    {
        $user = auth()->user();

        $favorite = Favorite::where('user_id', $user->id)
                        ->where('apartment_id', $apartmentId)
                        ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['favorite' => false]);
        }

        Favorite::create([
            'user_id' => $user->id,
            'apartment_id' => $apartmentId
        ]);

        return response()->json(['favorite' => true]);
    }

}
