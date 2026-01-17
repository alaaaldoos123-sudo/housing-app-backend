<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    // دالة للمستخدم لإرسال رسالة
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام رسالتك بنجاح، سيقوم الفريق بمراجعتها قريباً.'
        ], 201);
    }

    // دالة للأدمن لرؤية كل الرسائل
    public function index()
    {
        $tickets = SupportTicket::with('user:id,first_name,last_name,phone_number')
            ->latest()
            ->get();

        return response()->json($tickets);
    }
}
