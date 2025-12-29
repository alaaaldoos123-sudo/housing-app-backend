<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Apartment;
use App\Models\Booking; // ✅ ضروري لحساب الإحصائيات
use App\Models\Notification; // ✅ تمت إضافة موديل الإشعارات
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

    // عرض تفاصيل مستخدم معين
    public function showUser($userId)
    {
        try {
            $user = User::findOrFail($userId);

            return response()->json([
                'success' => true,
                'user' => $user
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }
    }

    // الموافقة على مستخدم جديد
    public function approveUser($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $user->status = 'active';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => "User approved successfully and is now active."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        } catch (\Exception $e) {
            Log::error("Approval failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error.'], 500);
        }
    }

    // رفض طلب تسجيل مستخدم جديد
    public function rejectUser($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $user->status = 'rejected';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => "User registration rejected."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
    }

    // حظر مستخدم فعال (Ban)
    public function banUser($userId)
    {

        try {
            $user = User::findOrFail($userId);

            $user->status = 'banned';
            $user->save();

            // حذف التوكنات لطرده من التطبيق فوراً
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => "User has been banned and logged out."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
    }

    // التحقق من حالة المستخدم
    public function checkStatus($userId)
    {
        try {
            $user = User::findOrFail($userId);
            return response()->json([
                'success' => true,
                'status' => $user->status,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    }


    // =========================================================
    // 2️⃣ قسم إدارة الشقق (Apartments Management)
    // =========================================================

    // جلب الشقق التي بانتظار الموافقة
    public function pendingApartments()
    {
        $apartments = Apartment::with('owner')
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Pending apartments fetched successfully.',
            'apartments' => $apartments
        ]);
    }

    // الموافقة على شقة ونشرها (مع إشعار)
    public function approveApartment($apartmentId)
    {
        try {
            $apartment = Apartment::findOrFail($apartmentId);

            $apartment->status = 'active';
            $apartment->is_published = true;
            $apartment->save();

            // ✅ إرسال إشعار للمالك
            try {
                Notification::create([
                    'user_id' => $apartment->owner_id,
                    'title'   => 'تمت الموافقة على عقارك! 🎉',
                    'body'    => "مبروك! تمت الموافقة على نشر عقارك '{$apartment->name}' وهو الآن متاح للمستأجرين.",
                    'type'    => 'apartment_approved',
                    'is_read' => false
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create notification for apartment approval: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => "Apartment approved, published, and owner notified."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Apartment not found.'], 404);
        }
    }

    // رفض شقة (مع إشعار)
    public function rejectApartment($apartmentId)
    {
        try {
            $apartment = Apartment::findOrFail($apartmentId);

            $apartment->status = 'rejected';
            $apartment->is_published = false;
            $apartment->save();

            // ✅ إرسال إشعار للمالك
            try {
                Notification::create([
                    'user_id' => $apartment->owner_id,
                    'title'   => 'تم رفض عقارك ❌',
                    'body'    => "نأسف لإبلاغك بأنه تم رفض نشر عقارك '{$apartment->name}'. يرجى مراجعة الشروط وتعديل البيانات.",
                    'type'    => 'apartment_rejected',
                    'is_read' => false
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create notification for apartment rejection: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => "Apartment rejected and owner notified."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Apartment not found.'], 404);
        }
    }

    // =========================================================
    // 3️⃣ الإحصائيات العامة (Dashboard Stats)
    // =========================================================

    public function getDashboardStats()
    {
        try {
            // 1. عدد المستخدمين الجدد (خلال هذا الشهر)
            $newUsers = User::where('user_role', '!=', 'admin')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            // 2. عدد الشقق التي تنتظر الموافقة
            $pendingApartments = Apartment::where('status', 'pending')->count();

            // 3. إجمالي الحجوزات
            $totalBookings = Booking::count();

            // 4. الدخل الكلي (مجموع أسعار الحجوزات المقبولة)
            $totalRevenue = Booking::where('status', 'accepted')->sum('total_price');

            return response()->json([
                'success' => true,
                'data' => [
                    'new_users' => $newUsers,
                    'pending_apartments' => $pendingApartments,
                    'total_bookings' => $totalBookings,
                    'total_revenue' => $totalRevenue
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // 4️⃣ إدارة شاملة (للعرض والبحث في كل البيانات) - 🔥 القسم الجديد المضاف
    // =========================================================

    // جلب جميع المستخدمين (مالكين ومستأجرين) مع البحث والفلترة
    public function getAllUsers(Request $request)
    {
        $query = User::where('user_role', '!=', 'admin'); // استثناء الأدمن

        // بحث بالاسم أو الهاتف
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('phone_number', 'like', "%$search%");
            });
        }

        // فلترة حسب الدور (مالك أو مستأجر)
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('user_role', $request->role);
        }

        // فلترة حسب الحالة (active, banned, pending)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // الترتيب: الأحدث أولاً
        $users = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    // جلب جميع الشقق (مع بيانات المالك والحجز الحالي)
    public function getAllApartments(Request $request)
    {
        // جلب الشقة مع المالك + الحجز النشط (إن وجد) ومستخدم الحجز
        $query = Apartment::with(['owner', 'activeBooking.user']);

        // بحث باسم الشقة أو المدينة
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%")
                    ->orWhere('location', 'like', "%$search%");
            });
        }

        // فلترة حسب الحالة (active, pending, rejected)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $apartments = $query->latest()->get();

        // نستخدم Resource لتوحيد شكل البيانات
        return \App\Http\Resources\ApartmentResource::collection($apartments);
    }
}
