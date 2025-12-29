<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification; // تأكد من استيراد الموديل

class NotificationController extends Controller
{
    // دالة جلب إشعارات المستخدم الحالي
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc') // الأحدث أولاً
            ->get();

        return response()->json($notifications);
    }

    // دالة تعليم الإشعار كمقروء
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->update(['is_read' => true]);
        }

        return response()->json(['message' => 'Marked as read']);
    }
}
