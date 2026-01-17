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

        $user = Auth::user();

        if ($user->wallet_balance < $totalPrice) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، رصيد المحفظة غير كافي لإتمام الحجز.'
            ], 422);
        }

        $user->wallet_balance -= $totalPrice;
        $user->save();

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
            'user_id' => $apartment->owner_id,
            'title'   => 'طلب حجز جديد 🏠',
            'body'    => 'لديك طلب حجز جديد من ' . Auth::user()->first_name . '، يرجى المراجعة.',
            'type'    => 'booking',
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم خصم المبلغ وإرسال طلب الحجز، بانتظار موافقة المالك',
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
            ->where('id', '!=', $id)
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

        $newTotalPrice = $days * $apartment->price;
        $priceDifference = $newTotalPrice - $booking->total_price;
        $user = Auth::user();

        if ($priceDifference > 0) {
            if ($user->wallet_balance < $priceDifference) {
                return response()->json([
                    'success' => false,
                    'message' => 'رصيد المحفظة غير كافي لدفع فرق السعر للتعديل.'
                ], 422);
            }
            $user->wallet_balance -= $priceDifference;
        } elseif ($priceDifference < 0) {
            $user->wallet_balance += abs($priceDifference);
        }

        $user->save();

        $booking->update([
            'check_in'    => $request->check_in,
            'check_out'   => $request->check_out,
            'total_price' => $newTotalPrice,
            'notes'       => $request->notes ?? $booking->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الحجز وتحديث الرصيد بنجاح',
            'data'    => new BookingResource($booking)
        ]);
    }

    public function cancel($id)
    {
        $booking = Booking::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        if (in_array($booking->status, ['completed', 'cancelled', 'rejected'])) {
            return response()->json(['message' => 'لا يمكن إلغاء هذا الحجز.'], 400);
        }

        $user = Auth::user();
        $user->wallet_balance += $booking->total_price;
        $user->save();

        $booking->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'تم إلغاء الحجز واستعادة المبلغ للمحفظة بنجاح']);
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

        if ($request->status == 'rejected') {
            $tenant = $booking->user;
            $tenant->wallet_balance += $booking->total_price;
            $tenant->save();
        }
        elseif ($request->status == 'accepted') {
            $owner = Auth::user();
            $owner->wallet_balance += $booking->total_price;
            $owner->save();
        }

        $booking->update([
            'status' => $request->status
        ]);

        $isAccepted = $request->status == 'accepted';

        Notification::create([
            'user_id' => $booking->user_id,
            'title'   => $isAccepted ? 'تمت الموافقة على حجزك! ✅' : 'تم رفض الحجز ❌',
            'body'    => $isAccepted
                ? 'وافق المالك على طلبك! نتمنى لك إقامة سعيدة.'
                : 'نعتذر منك، الشقة غير متاحة حالياً. تم استعادة المبلغ لمحفظتك.',
            'type'    => $isAccepted ? 'booking' : 'alert',
            'is_read' => false,
        ]);

        if ($request->status == 'accepted') {
            $conflictingBookings = Booking::where('apartment_id', $booking->apartment_id)
                ->where('id', '!=', $booking->id)
                ->where('status', 'pending')
                ->where(function ($q) use ($booking) {
                    $q->where('check_in', '<', $booking->check_out)
                        ->where('check_out', '>', $booking->check_in);
                })
                ->get();

            foreach ($conflictingBookings as $conflict) {
                $conflictTenant = $conflict->user;
                $conflictTenant->wallet_balance += $conflict->total_price;
                $conflictTenant->save();

                $conflict->update(['status' => 'rejected']);

                Notification::create([
                    'user_id' => $conflict->user_id,
                    'title'   => 'تم رفض الحجز ❌',
                    'body'    => 'نعتذر منك، تم حجز الشقة لمستأجر آخر في نفس التواريخ. تم استعادة المبلغ لمحفظتك.',
                    'type'    => 'alert',
                    'is_read' => false,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الحجز وتوزيع الأرصدة بنجاح',
            'data'    => new BookingResource($booking)
        ]);
    }
}
