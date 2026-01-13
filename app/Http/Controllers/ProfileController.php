<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Notification;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->json([
            'status' => true,
            'user' => auth()->user()
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'first_name'   => 'nullable|string|max:255',
            'last_name'    => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $user->id,
        ]);

        $user->update($data);

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'تنبيه أمني 🔐',
            'body'    => 'تم تعديل بيانات ملفك الشخصي بنجاح.',
            'type'    => 'alert',
            'is_read' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}
