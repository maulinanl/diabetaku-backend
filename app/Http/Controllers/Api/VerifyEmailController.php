<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Link verifikasi tidak valid.'
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email sudah terverifikasi.'
            ]);
        }

        $user->markEmailAsVerified();

        if ($user->role_id == 2) {
            $user->account_status = 'Menunggu Verifikasi';

            $message = 'Email berhasil diverifikasi. Akun menunggu verifikasi admin.';
        } else {
            $user->account_status = 'Aktif';

            $message = 'Email berhasil diverifikasi. Silakan login.';
        }
        $user->save();

        return response()->json([
            'message' => $message,
        ]);
    }
}
