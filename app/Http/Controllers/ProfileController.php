<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Notification; // 👈 ضروري عشان الإشعارات

class ProfileController extends Controller
{
    // عرض بيانات الملف الشخصي
    public function show()
    {
        return response()->json([
            'status' => true,
            'user' => auth()->user()
        ]);
    }

    // تعديل البيانات + إرسال إشعار
    public function update(Request $request)
    {
        $user = auth()->user();

        // 1. التحقق من البيانات القادمة
        $data = $request->validate([
            'first_name'   => 'nullable|string|max:255',
            'last_name'    => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $user->id,
            // لاحقاً بنضيف معالجة الصورة
        ]);

        // 2. تحديث البيانات
        $user->update($data);

        // 3. 👇 هون مربط الفرس: توليد إشعار تلقائي
        Notification::create([
            'user_id' => $user->id,
            'title'   => 'تنبيه أمني 🔐',
            'body'    => 'تم تعديل بيانات ملفك الشخصي بنجاح.',
            'type'    => 'alert', // نوعه تنبيه
            'is_read' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}
