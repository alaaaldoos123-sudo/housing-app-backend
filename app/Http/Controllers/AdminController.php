<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\ApartmentResource; // تأكد من وجود هذا السطر

class AdminController extends Controller
{
    // =========================================================
    // 1️⃣ قسم إدارة المستخدمين (Users Management)
    // =========================================================

    // جلب المستخدمين قيد الانتظار
    public function pendingUsers(Request $request)
    {
        $pendingUsers = User::where('user_role', '!=', 'admin')
            ->where('status', 'pending')
            ->latest()
            ->get([
                'id',
                'phone_number',
                'user_role',
                'first_name',
                'last_name',
                'created_at',
                'profile_image'
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Pending users list fetched successfully.',
            'users' => $pendingUsers
        ]);
    }

    // الموافقة على مستخدم
    public function approveUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->status = 'active';
            $user->save();

            return response()->json(['success' => true, 'message' => "User approved successfully."]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
    }

    // رفض مستخدم
    public function rejectUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->status = 'rejected';
            $user->save();

            return response()->json(['success' => true, 'message' => "User rejected."]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
    }

    // حظر مستخدم وطرد التوكنات
    public function banUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->status = 'banned';
            $user->save();
            $user->tokens()->delete(); // طرد المستخدم فوراً

            return response()->json(['success' => true, 'message' => "User banned and logged out."]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
    }

    // جلب جميع المستخدمين (للبحث والفلترة الشاملة)
    public function getAllUsers(Request $request)
    {
        $query = User::where('user_role', '!=', 'admin');

        // البحث بالاسم أو الهاتف
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('phone_number', 'like', "%$search%");
            });
        }

        // فلترة بالحالة
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // فلترة بالدور
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('user_role', $request->role);
        }

        return response()->json(['success' => true, 'data' => $query->latest()->get()]);
    }

    // =========================================================
    // 2️⃣ قسم إدارة الشقق (Apartments Management)
    // =========================================================

    // جلب الشقق المعلقة (للمراجعة)
    public function pendingApartments()
    {
        $apartments = Apartment::with('owner')
            ->where('status', 'pending')
            ->latest()
            ->get();

        // نستخدم Resource لضمان ترجمة الأسماء حسب لغة الأدمن
        return response()->json([
            'success' => true,
            'apartments' => ApartmentResource::collection($apartments)
        ]);
    }

    // الموافقة على عقار (مع إشعار)
    public function approveApartment($apartmentId)
    {
        try {
            $apartment = Apartment::findOrFail($apartmentId);
            $apartment->status = 'active';
            $apartment->is_published = true;
            $apartment->save();

            // إرسال إشعار للمالك
            Notification::create([
                'user_id' => $apartment->owner_id,
                'title'   => 'تمت الموافقة على عقارك! 🎉',
                'body'    => "مبروك! تمت الموافقة على نشر عقارك '{$apartment->name_ar}' وهو الآن متاح للمستأجرين.",
                'type'    => 'apartment_approved',
                'is_read' => false
            ]);

            return response()->json(['success' => true, 'message' => "Apartment approved."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    // رفض عقار (مع إشعار)
    public function rejectApartment($apartmentId)
    {
        try {
            $apartment = Apartment::findOrFail($apartmentId);
            $apartment->status = 'rejected';
            $apartment->is_published = false;
            $apartment->save();

            // إرسال إشعار للمالك
            Notification::create([
                'user_id' => $apartment->owner_id,
                'title'   => 'تم رفض عقارك ❌',
                'body'    => "نأسف لإبلاغك بأنه تم رفض نشر عقارك '{$apartment->name_ar}'.",
                'type'    => 'apartment_rejected',
                'is_read' => false
            ]);

            return response()->json(['success' => true, 'message' => "Apartment rejected."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    // جلب جميع الشقق (شاملة البحث باللغتين)
    public function getAllApartments(Request $request)
    {
        $query = Apartment::with(['owner', 'activeBooking.user']);

        // 🔥 البحث في الحقول العربية والإنكليزية
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'like', "%$search%")
                    ->orWhere('name_en', 'like', "%$search%")
                    ->orWhere('city_ar', 'like', "%$search%")
                    ->orWhere('city_en', 'like', "%$search%")
                    ->orWhere('location_ar', 'like', "%$search%")
                    ->orWhere('location_en', 'like', "%$search%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $apartments = $query->latest()->get();

        return ApartmentResource::collection($apartments);
    }

    // حذف نهائي للشقة (Force Delete)
    public function destroyApartment($id)
    {
        try {
            $apartment = Apartment::findOrFail($id);
            // هنا يمكنك إضافة كود لحذف الصور من التخزين إذا أردت
            $apartment->delete();
            return response()->json(['success' => true, 'message' => 'Apartment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    // =========================================================
    // 3️⃣ إدارة الحجوزات (Bookings Management) - 🔥 القسم الذي كان ناقصاً
    // =========================================================

    public function getAllBookings(Request $request)
    {
        try {
            // تحميل العلاقات (المستأجر، العقار ومالكه)
            $query = Booking::with(['user', 'apartment.owner']);

            // البحث باسم المستأجر أو اسم العقار (عربي/انكليزي)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%");
                })->orWhereHas('apartment', function($q) use ($search) {
                    $q->where('name_ar', 'like', "%$search%")
                        ->orWhere('name_en', 'like', "%$search%");
                });
            }

            // الفلترة حسب الحالة
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $bookings = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $bookings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching bookings: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================
    // 4️⃣ الإحصائيات العامة (Dashboard Stats)
    // =========================================================

    public function getDashboardStats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'new_users' => User::where('user_role', '!=', 'admin')->whereMonth('created_at', now()->month)->count(),
                'pending_apartments' => Apartment::where('status', 'pending')->count(),
                'total_bookings' => Booking::count(),
                'total_revenue' => Booking::where('status', 'accepted')->sum('total_price')
            ]
        ]);
    }
}
