<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ===========================================
// 1️⃣ مسارات عامة (Public) - لا تحتاج توكن
// ===========================================
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// التحقق من حالة المستخدم (للأدمن أو واجهة الانتظار)
Route::get('users/{userId}/status', [AdminController::class, 'checkStatus']);

// تصفح الشقق (عام للمستأجرين والزوار - يعرض المقبول فقط)
Route::get('apartments', [ApartmentController::class, 'index']);
Route::get('apartments/{id}', [ApartmentController::class, 'show']);


// ===========================================
// 2️⃣ مسارات محمية (Protected) - تحتاج تسجيل دخول
// ===========================================
Route::middleware('auth:sanctum')->group(function () {

    // --- المصادقة ---
    Route::post('logout', [AuthController::class, 'logout']);

    // --- المفضلة ---
    Route::post('apartments/{id}/favorite', [FavoriteController::class, 'toggle']);

    // --- إدارة الشقق (للمالك) ---
    // ✅ جلب عقارات المالك (بكل الحالات: معلقة، مقبولة، مرفوضة)
    Route::get('/my-apartments', [ApartmentController::class, 'myApartments']);

    // باقي العمليات
    Route::post('/apartments', [ApartmentController::class, 'store']);
    // قم بتغيير Route::post إلى Route::match ليقبل الحالتين
    Route::match(['put', 'post'], '/apartments/{id}', [ApartmentController::class, 'update']);
    Route::delete('/apartments/{id}', [ApartmentController::class, 'destroy']);

    // --- حجوزات المستأجر (Tenant) ---
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // --- لوحة تحكم المالك (Owner Dashboard) ---
    Route::get('/owner/my-properties', [OwnerController::class, 'myProperties']);
    Route::get('/owner/bookings', [OwnerController::class, 'getBookings']);
    Route::post('/owner/bookings/{id}/status', [OwnerController::class, 'updateBookingStatus']);

    // --- الملف الشخصي والإشعارات ---
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/profile/change-password', [AuthController::class, 'changePassword']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // --- الشات (Chat System) ---
    Route::post('/send_message', [ChatController::class, 'sendMessage']);
    Route::get('/get_messages', [ChatController::class, 'getMessages']);
    Route::get('/get_my_chats', [ChatController::class, 'getMyChats']);
});


// ===========================================
// 3️⃣ مسارات المدير (Admin) - لوحة التحكم
// ===========================================
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->prefix('admin')->group(function () {

    // --- أ) إدارة المستخدمين (الطلبات الجديدة) ---
    Route::get('users/pending', [AdminController::class, 'pendingUsers']); // جلب المنتظرين
    Route::get('users/{userId}', [AdminController::class, 'showUser']);    // عرض مستخدم
    Route::post('users/{userId}/approve', [AdminController::class, 'approveUser']); // موافقة
    Route::post('users/{userId}/reject', [AdminController::class, 'rejectUser']);   // رفض طلب التسجيل
    Route::post('users/{userId}/ban', [AdminController::class, 'banUser']);         // حظر مستخدم فعال

    // --- ب) إدارة الشقق (الموافقة المبدئية) ---
    Route::get('apartments/pending', [AdminController::class, 'pendingApartments']); // جلب الشقق المعلقة
    Route::post('apartments/{id}/approve', [AdminController::class, 'approveApartment']); // موافقة ونشر
    Route::post('apartments/{id}/reject', [AdminController::class, 'rejectApartment']);   // رفض الشقة

    // --- ج) الإحصائيات ---
    Route::get('/stats', [AdminController::class, 'getDashboardStats']);

    // 🔥🔥🔥 د) الإدارة الشاملة (الروابط الجديدة المضافة) 🔥🔥🔥

    // 1. عرض كل البيانات (مع البحث والفلترة)
    Route::get('all-users', [AdminController::class, 'getAllUsers']);         // كل المستخدمين
    Route::get('all-apartments', [AdminController::class, 'getAllApartments']); // كل الشقق
    Route::get('all-bookings', [AdminController::class, 'getAllBookings']);     // كل الحجوزات
    Route::get('/admin/all-bookings', [AdminController::class, 'getAllBookings']);
    // 2. إجراءات إضافية
    Route::post('users/{id}/activate', [AdminController::class, 'activateUser']); // إلغاء الحظر (Unban)
    Route::delete('apartments/{id}', [AdminController::class, 'forceDeleteApartment']); // حذف عقار نهائياً (Force Delete)
});
