<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Apartment;
use App\Http\Resources\BookingResource;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with('apartment.owner')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return BookingResource::collection($bookings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'check_in'     => 'required|date|after_or_equal:today',
            'check_out'    => 'required|date|after:check_in',
        ]);

        $apartment = Apartment::findOrFail($request->apartment_id);

        if ($apartment->owner_id == Auth::id()) {
            return response()->json(['message' => 'لا يمكنك حجز عقارك الخاص'], 400);
        }

        $isBooked = Booking::where('apartment_id', $request->apartment_id)
            ->whereIn('status', ['pending', 'confirmed', 'accepted'])
            ->where(function ($query) use ($request) {
                $query->where('check_in', '<', $request->check_out)
                    ->where('check_out', '>', $request->check_in);
            })->exists();

        if ($isBooked) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، الشقة محجوزة في هذه التواريخ.'
            ], 422);
        }

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $days = $checkIn->diffInDays($checkOut);
        if ($days == 0) $days = 1;

        $totalPrice = $days * $apartment->price;

        $booking = Booking::create([
            'user_id'      => Auth::id(),
            'apartment_id' => $request->apartment_id,
            'check_in'     => $request->check_in,
            'check_out'    => $request->check_out,
            'total_price'  => $totalPrice,
            'status'       => 'pending',
            'notes'        => $request->notes
        ]);

        Notification::create([
            'user_id' => $apartment->owner_id, // صاحب الشقة
            'title'   => 'طلب حجز جديد 🏠',
            'body'    => 'لديك طلب حجز جديد من ' . Auth::user()->first_name . '، يرجى المراجعة.',
            'type'    => 'booking',
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الحجز، بانتظار موافقة المالك',
            'data'    => new BookingResource($booking->load('apartment'))
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'check_in'     => 'required|date|after_or_equal:today',
            'check_out'    => 'required|date|after:check_in',
        ]);

        $booking = Booking::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل تفاصيل الحجز بعد قبوله أو رفضه.'
            ], 400);
        }

        $isBooked = Booking::where('apartment_id', $booking->apartment_id)
            ->where('id', '!=', $id) // 👈 مهم جداً: استثناء الحجز الحالي
            ->whereIn('status', ['pending', 'confirmed', 'accepted'])
            ->where(function ($query) use ($request) {
                $query->where('check_in', '<', $request->check_out)
                    ->where('check_out', '>', $request->check_in);
            })->exists();

        if ($isBooked) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، الشقة محجوزة في الموعد الجديد الذي اخترته.'
            ], 422);
        }

        $apartment = Apartment::findOrFail($booking->apartment_id);
        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $days = $checkIn->diffInDays($checkOut);
        if ($days == 0) $days = 1;
        $totalPrice = $days * $apartment->price;

        $booking->update([
            'check_in'    => $request->check_in,
            'check_out'   => $request->check_out,
            'total_price' => $totalPrice,
            'notes'       => $request->notes ?? $booking->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الحجز بنجاح',
            'data'    => new BookingResource($booking)
        ]);
    }

    public function cancel($id)
    {
        $booking = Booking::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        if (in_array($booking->status, ['completed', 'cancelled', 'rejected'])) {
            return response()->json(['message' => 'لا يمكن إلغاء هذا الحجز.'], 400);
        }

        $booking->update(['status' => 'cancelled']);


        return response()->json(['success' => true, 'message' => 'تم إلغاء الحجز بنجاح']);
    }

    public function ownerRequests()
    {
        $requests = Booking::whereHas('apartment', function ($query) {
            $query->where('owner_id', Auth::id());
        })
            ->with(['user', 'apartment'])
            ->latest()
            ->get();

        return BookingResource::collection($requests);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected'
        ]);

        $booking = Booking::whereHas('apartment', function ($query) {
            $query->where('owner_id', Auth::id());
        })->findOrFail($id);

        $booking->update([
            'status' => $request->status
        ]);

        $isAccepted = $request->status == 'accepted';

        Notification::create([
            'user_id' => $booking->user_id, // المستأجر
            'title'   => $isAccepted ? 'تمت الموافقة على حجزك! ✅' : 'تم رفض الحجز ❌',
            'body'    => $isAccepted
                ? 'وافق المالك على طلبك! يرجى إتمام الدفع لتثبيت الحجز.'
                : 'نعتذر منك، الشقة غير متاحة حالياً.',
            'type'    => $isAccepted ? 'booking' : 'alert',
            'is_read' => false,
        ]);


        if ($request->status == 'accepted') {
            Booking::where('apartment_id', $booking->apartment_id)
                ->where('id', '!=', $booking->id)
                ->where('status', 'pending')
                ->where(function ($q) use ($booking) {
                    $q->where('check_in', '<', $booking->check_out)
                        ->where('check_out', '>', $booking->check_in);
                })
                ->update(['status' => 'rejected']);

        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الحجز بنجاح إلى ' . ($request->status == 'accepted' ? 'مقبول' : 'مرفوض'),
            'data'    => new BookingResource($booking)
        ]);
    }
}
