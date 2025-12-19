<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $data = $request->validate([
            'phone_number' => 'required|string|unique:users,phone_number',
            'user_role'    => 'required|in:tenant,owner,admin',
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'birth_date'   => 'required|date',

            'profile_image' => 'nullable|image|max:5120',
            'id_image'      => 'nullable|image|max:5120',
            'password'      => 'required|min:6',
        ]);

        $avatarPath = null;
        $identityPath = null;


        try {
            if ($request->hasFile('profile_image')) {
                $avatarPath = $request->file('profile_image')->store('avatars', 'public');
            }

            if ($request->hasFile('id_image')) {
                $identityPath = $request->file('id_image')->store('identity', 'public');
            }
        } catch (\Exception $e) {
            \Log::error('File Upload Failed during Registration: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process image uploads. Check server permissions.',
            ], 500);
        }


        $isApproved = in_array($data['user_role'], ['tenant', 'owner']) ? false : true;

        $user = User::create([
            'phone_number' => $data['phone_number'],
            'user_role'    => $data['user_role'],
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'birth_date'   => $data['birth_date'],
            'password'     => Hash::make($data['password']),

            'avatar'         => $avatarPath,
            'identity_image' => $identityPath,

            'is_approved'    => $isApproved,
            'status'         => $isApproved ? 'active' : 'pending',
        ]);

        $token = $user->createToken('auth')->plainTextToken;


        return response()->json([
            'success' => true,
            'message' => 'Account created successfully. Awaiting admin approval.',
            'user_id' => $user->id,
            'user_status' => $user->status,
            'token' => $token,
            'user_role' => $user->user_role,
        ], 201);
    }
    public function login(Request $request)
    {

        $data = $request->validate([
            'phone_number' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('phone_number', $data['phone_number'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid phone number or password'], 401);
        }

        $token = $user->createToken('auth')->plainTextToken;


        $userData = $user->only(['id', 'phone_number', 'user_role', 'is_approved', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'token' => $token,
            'user' => $userData
        ]);
    }

}
