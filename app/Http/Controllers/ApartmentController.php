<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return Apartment::with('owner')
            ->get()
            ->map(function ($apartment) use ($user) {
                $apartment->is_favorite = $user
                    ? $apartment->favorites()->where('user_id', $user->id)->exists()
                    : false;

                return $apartment;
            });
    }

    public function show($id)
    {
        $user = auth()->user();

        $apartment = Apartment::with('owner')->findOrFail($id);

        $apartment->is_favorite = $user
            ? $apartment->favorites()->where('user_id', $user->id)->exists()
            : false;

        return $apartment;
    }

}
