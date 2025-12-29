<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'phone_number'  => 'required|string|unique:users,phone_number',
            'user_role'     => 'required|in:tenant,owner,admin',
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'birth_date'    => 'required|date',
            'password'      => 'required|min:6',
            'profile_image' => 'nullable|image|max:5120',
            'id_image'      => 'nullable|image|max:5120',
        ]);

        $profilePath = null;
        $idPath = null;

        try {
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profiles', 'public');
                $profilePath = asset('storage/' . $path);
            }

            if ($request->hasFile('id_image')) {
                $path = $request->file('id_image')->store('ids', 'public');
                $idPath = asset('storage/' . $path);
            }
        } catch (\Exception $e) {
            Log::error('File Upload Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في رفع الصور، يرجى المحاولة لاحقاً.',
            ], 500);
        }

        $initialStatus = in_array($data['user_role'], ['tenant', 'owner']) ? 'pending' : 'active';

        $user = User::create([
            'phone_number' => $data['phone_number'],
            'user_role'    => $data['user_role'],
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'birth_date'   => $data['birth_date'],
            'password'     => Hash::make($data['password']),
            'profile_image' => $profilePath,
            'id_image'      => $idPath,
            'status'        => $initialStatus,
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'success'     => true,
            'message'     => 'تم إنشاء الحساب بنجاح، بانتظار الموافقة.',
            'user_id'     => $user->id,
            'user_status' => $user->status,
            'token'       => $token,
            'user'        => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone_number' => 'required',
            'password'     => 'required'
        ]);

        $user = User::where('phone_number', $data['phone_number'])->first();

        // 1. التحقق من صحة كلمة المرور
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'رقم الهاتف أو كلمة المرور غير صحيحة'], 401);
        }

        // 🔥 2. (الإضافة الجديدة) منع الدخول إذا كان المستخدم محظوراً
        if ($user->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، هذا الحساب تم حظره من قبل الإدارة.',
            ], 403); // كود 403 يعني ممنوع
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => $user,
            'user_status' => $user->status
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'phone_number'  => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'profile_image' => 'nullable|image|max:5120',
        ]);

        try {
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->phone_number = $data['phone_number'];

            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profiles', 'public');
                $user->profile_image = asset('storage/' . $path);
            }

            $user->save();

            try {
                Notification::create([
                    'user_id' => $user->id,
                    'title'   => 'تحديث الملف الشخصي 👤',
                    'body'    => 'تم تحديث بيانات حسابك الشخصي بنجاح.',
                    'type'    => 'alert',
                    'is_read' => false,
                ]);
            } catch (\Exception $e) {
                Log::error("Notification Error: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الملف الشخصي بنجاح',
                'user'    => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة',
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'تغيير كلمة المرور 🔐',
            'body'    => 'تم تغيير كلمة المرور الخاصة بحسابك بنجاح. إذا لم تكن أنت، يرجى التواصل معنا.',
            'type'    => 'alert',
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    // دالة تسجيل الخروج (أضفتها لك للاحتياط إذا لم تكن موجودة، وهي آمنة ولا تخرب شيء)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
