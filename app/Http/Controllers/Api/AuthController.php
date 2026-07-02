<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Controllers\Controller;


class AuthController extends Controller
{
    public function registerDoctor(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'password' => 'required|string|min:8|confirmed',
            'specialization_id' => 'required|exists:specializations,specialization_id',
            'str_number' => 'required|string|max:50|unique:doctors,str_number',
            'institution' => 'required|string|max:200',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'role_id' => 2,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'gender' => $validated['gender'],
                'password_hash' => Hash::make($validated['password']),
                'account_status' => 'Menunggu Verifikasi',
            ]);

            Doctor::create([
                'user_id' => $user->user_id,
                'specialization_id' => $validated['specialization_id'],
                'str_number' => $validated['str_number'],
                'institution' => $validated['institution'],
                'verification_status' => 'Menunggu',
            ]);

            event(new Registered($user));

            DB::commit();

            return response()->json([
                'message' => 'Registrasi berhasil. Silakan cek email untuk verifikasi akun.',
                'data' => [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Registrasi gagal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function registerPatient(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'password' => 'required|min:8|confirmed',
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'diabetes_type' => ['required', Rule::in(['Tipe 1', 'Tipe 2'])],
            'date_of_birth' => 'required|date',
            'diagnosis_date' => 'required|date',
            'height_cm' => 'required|numeric',
            'blood_type_id' => 'required|exists:blood_types,blood_type_id',
            'rhesus_type_id' => 'required|exists:rhesus_types,rhesus_type_id',
        ]);

        $userId = DB::transaction(function () use ($request) {
            $userId = DB::table('users')->insertGetId([
                'role_id' => 3,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
                'account_status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'user_id');

            DB::table('patients')->insert([
                'user_id' => $userId,
                'date_of_birth' => $request->date_of_birth,
                'diabetes_type' => $request->diabetes_type,
                'diagnosis_date' => $request->diagnosis_date,
                'height_cm' => $request->height_cm,
                'blood_type_id' => $request->blood_type_id,
                'rhesus_type_id' => $request->rhesus_type_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $userId;
        });

        $user = User::find($userId);
        event(new Registered($user));

        return response()->json([
            'message' => 'Registrasi pasien berhasil'
        ], 201);
    }

    public function registerCaregiver(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|max:20',
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
        ]);

        $data = DB::transaction(function () use ($request) {
            $userId = DB::table('users')->insertGetId([
                'role_id' => 4,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
                'account_status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'user_id');

            $caregiverId = DB::table('caregivers')->insertGetId([
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'caregiver_id');

            return [
                'user_id' => $userId,
                'caregiver_id' => $caregiverId,
            ];
        });

        $user = User::find($data['user_id']);
        event(new Registered($user));

        return response()->json([
            'message' => 'Registrasi pendamping berhasil. Silakan cek email untuk verifikasi akun.',
            'data' => $data
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'invalid_credentials',
                'message' => 'Email atau kata sandi salah',
            ], 401);
        }

        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            return response()->json([
                'status' => 'account_locked',
                'message' => 'Akun dikunci sementara. Coba lagi setelah 30 menit.',
                'locked_until' => $user->locked_until,
            ], 423);
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            $attempts = ((int) $user->login_attempts) + 1;

            $updateData = [
                'login_attempts' => $attempts,
                'updated_at' => now(),
            ];

            if ($attempts >= 5) {
                $updateData['locked_until'] = now()->addMinutes(30);
            }

            DB::table('users')
                ->where('user_id', $user->user_id)
                ->update($updateData);

            return response()->json([
                'status' => $attempts >= 5 ? 'account_locked' : 'invalid_credentials',
                'message' => $attempts >= 5
                    ? 'Akun dikunci sementara. Coba lagi setelah 30 menit.'
                    : 'Email atau kata sandi salah',
                'login_attempts' => $attempts,
                'locked_until' => $attempts >= 5 ? $updateData['locked_until'] : null,
            ], $attempts >= 5 ? 423 : 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'email_unverified',
                'message' => 'Email belum diverifikasi',
                'email' => $user->email,
                'role_id' => $user->role_id,
            ], 403);
        }

        $doctor = null;
        $patient = null;
        $caregiver = null;

        if ($user->role_id == 2) {
            $doctor = Doctor::where('user_id', $user->user_id)->first();

            if (!$doctor) {
                return response()->json([
                    'status' => 'doctor_not_found',
                    'message' => 'Data dokter tidak ditemukan',
                ], 404);
            }

            if ($doctor->verification_status === 'Ditolak') {
                return response()->json([
                    'status' => 'admin_rejected',
                    'message' => 'Registrasi dokter ditolak admin',
                ], 403);
            }

            if ($doctor->verification_status !== 'Disetujui') {
                return response()->json([
                    'status' => 'admin_unverified',
                    'message' => 'Akun dokter sedang menunggu verifikasi admin',
                    'email' => $user->email,
                ], 403);
            }
        }

        if ($user->account_status !== 'Aktif') {
            return response()->json([
                'status' => 'account_inactive',
                'message' => 'Akun belum aktif',
            ], 403);
        }

        if ($user->role_id == 3) {
            $patient = DB::table('patients')
                ->where('user_id', $user->user_id)
                ->first();
        }

        if ($user->role_id == 4) {
            $caregiver = DB::table('caregivers')
                ->where('user_id', $user->user_id)
                ->first();
        }

        $user->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);

        $token = $user->createToken('diabetaku-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh(),
            'doctor' => $doctor,
            'patient' => $patient,
            'caregiver' => $caregiver,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($request->filled('fcm_token')) {
            DB::table('user_fcm_tokens')
                ->where('user_id', $user->user_id)
                ->where('fcm_token', $request->fcm_token)
                ->update([
                    'is_active' => false,
                    'logged_out_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check(
            $request->current_password,
            $user->password_hash
        )) {
            return response()->json([
                'message' => 'Kata sandi lama tidak sesuai'
            ], 422);
        }

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'password_hash' => Hash::make(
                    $request->new_password
                ),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Kata sandi berhasil diperbarui'
        ]);
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan'
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email sudah terverifikasi'
            ], 422);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Email verifikasi berhasil dikirim ulang'
        ]);
    }

    public function checkEmailVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Email tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => $user->hasVerifiedEmail()
                ? 'verified'
                : 'unverified',
            'message' => $user->hasVerifiedEmail()
                ? 'Email sudah terverifikasi'
                : 'Email belum diverifikasi',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Link reset password berhasil dikirim'
            ]);
        }

        return response()->json([
            'message' => 'Email tidak ditemukan'
        ], 404);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) {
                $user->password_hash = Hash::make($password);
                $user->login_attempts = 0;
                $user->locked_until = null;
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password berhasil diubah'
            ]);
        }

        return response()->json([
            'message' => 'Token reset password tidak valid atau sudah kedaluwarsa'
        ], 400);
    }

    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $exists = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($request->email))])
            ->exists();

        return response()->json([
            'message' => 'Pengecekan email berhasil',
            'exists' => $exists,
        ]);
    }

}
