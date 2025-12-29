<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Apartment;
use App\Models\Booking;
use App\Http\Resources\ApartmentResource;

class OwnerController extends Controller
{
    public function myProperties(Request $request)
    {
        $user = $request->user();

        $apartments = Apartment::where('owner_id', $user->id)
            ->with(['activeBooking.user'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => ApartmentResource::collection($apartments),
        ]);
    }

    public function getBookings(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::whereHas('apartment', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })
            ->with(['user', 'apartment'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'check_in' => $booking->check_in,
                    'check_out' => $booking->check_out,
                    'total_price' => (double) $booking->total_price,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                    'user' => $booking->user,
                    'apartment' => $booking->apartment,
                ];
            })
        ]);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected'
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->apartment->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status updated']);
    }
}
