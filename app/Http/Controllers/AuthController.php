<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            'profile_image' => 'nullable|image|max:5120', // 5MB Max
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
            return response()->json(['success' => false, 'message' => 'فشل رفع الصور'], 500);
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
            'message'     => 'تم إنشاء الحساب، بانتظار الموافقة.',
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

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        if ($user->status === 'banned') {
            return response()->json(['success' => false, 'message' => 'حسابك محظور'], 403);
        }

        if ($user->is_2fa_enabled) {
            $otpCode = rand(1000, 9999);
            $user->verification_code = $otpCode;
            $user->save();

            $message = "رمز التحقق الخاص بك: $otpCode";
            $this->sendWhatsAppMessage($user->phone_number, $message);

            return response()->json([
                'success'      => true,
                'requires_2fa' => true,
                'message'      => 'يرجى إدخال رمز التحقق المرسل للواتساب',
                'phone_number' => $user->phone_number
            ]);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $user,
            'user_status' => $user->status
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'code'         => 'required',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user || $user->verification_code !== $request->code) {
            return response()->json(['success' => false, 'message' => 'الرمز غير صحيح'], 400);
        }

        $user->verification_code = null;
        $user->save();

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق بنجاح',
            'token'   => $token,
            'user'    => $user,
            'user_status' => $user->status
        ]);
    }

    public function toggleTwoFactor(Request $request)
    {
        $request->validate(['enable' => 'required|boolean']);
        $user = $request->user();
        $user->is_2fa_enabled = $request->enable;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_2fa_enabled ? 'تم التفعيل' : 'تم الإيقاف',
            'is_2fa_enabled' => $user->is_2fa_enabled
        ]);
    }

    public function checkStatus(Request $request)
    {
        $user = $request->user();
        if ($user) {
            return response()->json([
                'success' => true,
                'status' => $user->status,
                'user_role' => $user->user_role,
                'is_2fa_enabled' => $user->is_2fa_enabled,
            ]);
        }
        return response()->json(['success' => false], 404);
    }



    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'phone_number' => 'required|string|unique:users,phone_number,' . $request->user()->id,
            'profile_image'=> 'nullable|image|max:5120',
        ]);

        $user = $request->user();

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone_number = $request->phone_number;

        if ($request->hasFile('profile_image')) {

            $path = $request->file('profile_image')->store('profiles', 'public');
            $user->profile_image = asset('storage/' . $path);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'user'    => $user,
        ], 200);
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ]);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    private function sendWhatsAppMessage(string $to, string $message): bool
    {
        $to = str_replace(' ', '', $to);
        if (substr($to, 0, 2) == '09') {
            $to = '963' . substr($to, 1);
        }

        $token = env('ULTRAMSG_TOKEN');
        $instanceUrl = env('ULTRAMSG_API_URL');

        if (env('ENABLE_WHATSAPP_NOTIFICATIONS') != true) {
            Log::info("WhatsApp Sending Disabled. Message to $to: $message");
            return true;
        }

        $params = [
            'token' => $token,
            'to' => $to,
            'body' => $message,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $instanceUrl . "/messages/chat",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => [
                "content-type: application/x-www-form-urlencoded"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            Log::error("UltraMsg Error: $err");
            return false;
        }

        Log::info("UltraMsg Response: $response");
        return true;
    }
}
