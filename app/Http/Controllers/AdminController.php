<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminController extends Controller
{

    public function pendingUsers(Request $request)
    {

        $pendingUsers = User::where('user_role', '!=', 'admin')
            ->where('is_approved', false)
            ->where('status', 'pending')
            ->get([
                'id',
                'phone_number',
                'user_role',
                'first_name',
                'last_name',
                'created_at'
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Pending users list fetched successfully.',
            'users' => $pendingUsers
        ]);
    }

    public function showUser($userId)
    {
        try {
            $user = User::findOrFail($userId, [
                'id',
                'phone_number',
                'user_role',
                'first_name',
                'last_name',
                'birth_date',
                'avatar',
                'identity_image',
                'created_at'
            ]);

            if ($user->user_role === 'admin' || $user->is_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already active or is an admin.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'user' => $user
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }
    }


    public function approveUser($userId)
    {
        try {
            $user = User::findOrFail($userId);


            $user->is_approved = true;
            $user->status = 'active';
            $user->save();



            return response()->json([
                'success' => true,
                'message' => "User {$userId} approved successfully and is now active."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Approval failed for user {$userId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error during approval.'
            ], 500);
        }
    }


    public function rejectUser($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $user->is_approved = false;
            $user->status = 'rejected';
            $user->save();


            return response()->json([
                'success' => true,
                'message' => "User {$userId} rejected successfully."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }
    }
}
