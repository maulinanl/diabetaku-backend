<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);
        $appUrl = config('app.mobile_deeplink', env('APP_DEEPLINK_URL', 'diabetaku://login'));

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->view('auth.email-verified', [
                'status' => 'error',
                'title' => 'Link Verifikasi Tidak Valid',
                'message' => 'Link verifikasi email tidak valid. Silakan minta link verifikasi baru melalui aplikasi DiabetAku.',
                'instruction' => 'Buka aplikasi DiabetAku, masuk ke halaman login, lalu gunakan fitur kirim ulang verifikasi email jika tersedia.',
                'badgeText' => 'Verifikasi gagal',
                'appUrl' => $appUrl,
                'showOpenAppButton' => true,
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->view('auth.email-verified', [
                'status' => 'info',
                'title' => 'Email Sudah Terverifikasi',
                'message' => 'Email kamu sudah berhasil diverifikasi sebelumnya.',
                'instruction' => 'Silakan buka kembali aplikasi DiabetAku dan login menggunakan akun yang sudah kamu daftarkan.',
                'badgeText' => 'Sudah terverifikasi',
                'appUrl' => $appUrl,
                'showOpenAppButton' => true,
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ((int) $user->role_id === 2) {
            $user->account_status = 'Menunggu Verifikasi';

            $title = 'Email Berhasil Diverifikasi';
            $message = 'Email dokter berhasil diverifikasi. Akun kamu sedang menunggu verifikasi admin sebelum dapat digunakan.';
            $instruction = 'Silakan tunggu proses verifikasi admin. Setelah disetujui, kamu dapat login melalui aplikasi DiabetAku.';
            $badgeText = 'Menunggu verifikasi admin';
        } else {
            $user->account_status = 'Aktif';

            $title = 'Email Berhasil Diverifikasi';
            $message = 'Email kamu berhasil diverifikasi. Akun sudah aktif dan siap digunakan.';
            $instruction = 'Silakan buka kembali aplikasi DiabetAku, lalu login menggunakan email dan password yang sudah kamu daftarkan.';
            $badgeText = 'Akun aktif';
        }

        $user->save();

        return response()->view('auth.email-verified', [
            'status' => 'success',
            'title' => $title,
            'message' => $message,
            'instruction' => $instruction,
            'badgeText' => $badgeText,
            'appUrl' => $appUrl,
            'showOpenAppButton' => true,
        ]);
    }
}
