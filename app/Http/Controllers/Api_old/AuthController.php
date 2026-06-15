<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'Email atau kata sandi salah'
            ], 401);
        }

        if ($user->account_status !== 'Aktif') {
            return response()->json([
                'message' => 'Akun belum aktif atau sedang terkunci'
            ], 403);
        }

        $token = $user->createToken('diabetaku-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'user_id' => $user->user_id,
                'role_id' => $user->role_id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'phone_number' => $user->phone_number,
                'gender' => $user->gender,
                'account_status' => $user->account_status,
            ]
        ]);
    }
}
