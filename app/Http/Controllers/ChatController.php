<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    // ==========================================================
    // 1. إرسال رسالة (Send Message)
    // ==========================================================
    public function sendMessage(Request $request)
    {
        // 1. التحقق من البيانات القادمة
        $request->validate([
            'sender_id'   => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'text'        => 'required|string',
            'property_id' => 'nullable|exists:apartments,id',
        ]);

        // 2. إنشاء الرسالة وتخزينها
        $message = Message::create([
            'sender_id'   => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'property_id' => $request->property_id,
            'text'        => $request->text,
            'is_read'     => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Message sent successfully',
            'data' => $message
        ], 200);
    }

    // ==========================================================
    // 2. جلب رسائل المحادثة (Get Messages)
    // ==========================================================
    public function getMessages(Request $request)
    {
        $request->validate([
            'user_id'  => 'required',
            'other_id' => 'required',
        ]);

        $userId = $request->user_id;
        $otherId = $request->other_id;

        // جلب الرسائل بين الطرفين وترتيبها من الأقدم للأحدث
        $messages = Message::where(function($q) use ($userId, $otherId) {
            $q->where('sender_id', $userId)->where('receiver_id', $otherId);
        })
            ->orWhere(function($q) use ($userId, $otherId) {
                $q->where('sender_id', $otherId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // تنسيق البيانات لتناسب Flutter MessageModel
        $formattedMessages = $messages->map(function ($msg) {
            return [
                'id'         => (string)$msg->id, // تحويل لنص للأمان في فلاتر
                'senderId'   => (string)$msg->sender_id,
                'receiverId' => (string)$msg->receiver_id,
                'text'       => $msg->text,
                'timestamp'  => $msg->created_at->toIso8601String(), // صيغة الوقت القياسية
                'isRead'     => (bool)$msg->is_read,
                'type'       => 'text'
            ];
        });

        return response()->json($formattedMessages, 200);
    }

    // ==========================================================
    // 3. جلب قائمة المحادثات (Get My Chats)
    // ==========================================================
    public function getMyChats(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);

        $userId = $request->user_id;

        // استراتيجية ذكية: نجلب كل الرسائل التي تخصني، نرتبها بالأحدث، ثم نأخذ واحدة فريدة لكل محادثة
        // هذه الطريقة أسهل وأسرع في Eloquent من كتابة Complex Raw SQL

        $allMessages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver']) // Eager Load لجلب بيانات المستخدمين بسرعة
            ->orderBy('created_at', 'desc')
            ->get();

        $chats = [];
        $processedUserIds = [];

        foreach ($allMessages as $msg) {
            // تحديد من هو الطرف الآخر في هذه الرسالة
            $isMeSender = $msg->sender_id == $userId;
            $otherUser = $isMeSender ? $msg->receiver : $msg->sender;

            // إذا لم يكن هناك طرف آخر (مثلاً المستخدم محذوف)، نتجاوز
            if (!$otherUser) continue;

            // إذا كنا قد أضفنا هذا الشخص للقائمة مسبقاً، نتجاوز (لأننا رتبنا بالأحدث، فالأول هو الأحدث)
            if (in_array($otherUser->id, $processedUserIds)) {
                continue;
            }

            // إضافة الـ ID للمصفوفة لعدم تكراره
            $processedUserIds[] = $otherUser->id;

            // تجهيز رابط الصورة (URL كامل)
            // ملاحظة: تأكد من أنك تخزن الصور في public أو storage linked
            $imageUrl = $otherUser->profile_image
                ? asset('storage/' . $otherUser->profile_image) // أو المسار الذي تستخدمه
                : '';

            // دمج الاسم الأول والأخير
            $fullName = $otherUser->first_name . ' ' . $otherUser->last_name;

            // إضافة المحادثة للقائمة
            $chats[] = [
                'chatRoomId'  => $userId < $otherUser->id ? "{$userId}_{$otherUser->id}" : "{$otherUser->id}_{$userId}",
                'lastMessage' => $msg->text,
                'lastTime'    => $msg->created_at->toIso8601String(),
                'propertyId'  => (string)$msg->property_id,
                'users'       => [(string)$userId, (string)$otherUser->id],

                // 🔥 البيانات المهمة للقائمة (الاسم والصورة)
                'otherUserInfo' => [
                    'id'    => (string)$otherUser->id,
                    'name'  => $fullName,
                    'image' => $imageUrl
                ]
            ];
        }

        return response()->json($chats, 200);
    }
}
