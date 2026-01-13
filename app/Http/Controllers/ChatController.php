<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{

    public function sendMessage(Request $request)
    {
        $request->validate([
            'sender_id'   => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'text'        => 'required|string',
            'property_id' => 'nullable|exists:apartments,id',
        ]);

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
    public function getMessages(Request $request)
    {
        $request->validate([
            'user_id'  => 'required',
            'other_id' => 'required',
        ]);

        $userId = $request->user_id;
        $otherId = $request->other_id;
    Message::where('sender_id', $otherId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where(function($q) use ($userId, $otherId) {
            $q->where('sender_id', $userId)->where('receiver_id', $otherId);
        })
            ->orWhere(function($q) use ($userId, $otherId) {
                $q->where('sender_id', $otherId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $formattedMessages = $messages->map(function ($msg) {
            return [
                'id'         => (string)$msg->id,
                'senderId'   => (string)$msg->sender_id,
                'receiverId' => (string)$msg->receiver_id,
                'text'       => $msg->text,
                'timestamp'  => $msg->created_at->toIso8601String(),
                'isRead'     => (bool)$msg->is_read,
                'type'       => 'text'
            ];
        });

        return response()->json($formattedMessages, 200);
    }

   public function getMyChats(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);

        $userId = $request->user_id;

        $allMessages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get();

        $chats = [];
        $processedUserIds = [];

        foreach ($allMessages as $msg) {
            $isMeSender = $msg->sender_id == $userId;
            $otherUser = $isMeSender ? $msg->receiver : $msg->sender;

            if (!$otherUser) continue;

            if (in_array($otherUser->id, $processedUserIds)) {
                continue;
            }

            $processedUserIds[] = $otherUser->id;

            $imageUrl = $otherUser->profile_image
                ? asset('storage/' . $otherUser->profile_image)
                : '';

            $fullName = $otherUser->first_name . ' ' . $otherUser->last_name;

            $unreadCount = Message::where('sender_id', $otherUser->id)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->count();

            $chats[] = [
                'chatRoomId'  => $userId < $otherUser->id ? "{$userId}_{$otherUser->id}" : "{$otherUser->id}_{$userId}",
                'lastMessage' => $msg->text,
                'lastTime'    => $msg->created_at->toIso8601String(),
                'propertyId'  => (string)$msg->property_id,
                'unreadCounts' => $unreadCount,
                'users'       => [(string)$userId, (string)$otherUser->id],
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
