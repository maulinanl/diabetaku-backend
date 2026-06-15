<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registerDoctor(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'specialization_id' => 'required|exists:specializations,specialization_id',
            'str_number' => 'required|string|max:50|unique:doctors,str_number',
            'institution' => 'nullable|string|max:200',
        ]);

        $data = DB::transaction(function () use ($request) {
            $userId = DB::table('users')->insertGetId([
                'role_id' => 2,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'account_status' => 'Menunggu Verifikasi',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'user_id');

            $doctorId = DB::table('doctors')->insertGetId([
                'user_id' => $userId,
                'specialization_id' => $request->specialization_id,
                'str_number' => $request->str_number,
                'institution' => $request->institution,
                'verification_status' => 'Menunggu',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'doctor_id');

            return [
                'user_id' => $userId,
                'doctor_id' => $doctorId,
            ];
        });

        return response()->json([
            'message' => 'Registrasi dokter berhasil. Akun menunggu verifikasi admin.',
            'data' => $data
        ], 201);
    }

    public function registerPatient(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'diabetes_type' => 'required|in:Tipe 1,Tipe 2',
            'diagnosis_date' => 'nullable|date',
            'height_cm' => 'nullable|numeric',
            'blood_type_id' => 'nullable|exists:blood_types,blood_type_id',
            'rhesus_type_id' => 'nullable|exists:rhesus_types,rhesus_type_id',
        ]);

        $data = DB::transaction(function () use ($request) {
            $userId = DB::table('users')->insertGetId([
                'role_id' => 3,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'account_status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'user_id');

            $patientId = DB::table('patients')->insertGetId([
                'user_id' => $userId,
                'diabetes_type' => $request->diabetes_type,
                'diagnosis_date' => $request->diagnosis_date,
                'height_cm' => $request->height_cm,
                'blood_type_id' => $request->blood_type_id,
                'rhesus_type_id' => $request->rhesus_type_id,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'patient_id');

            return [
                'user_id' => $userId,
                'patient_id' => $patientId,
            ];
        });

        return response()->json([
            'message' => 'Registrasi pasien berhasil.',
            'data' => $data
        ], 201);
    }

    public function registerFamily(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
        ]);

        $data = DB::transaction(function () use ($request) {
            $userId = DB::table('users')->insertGetId([
                'role_id' => 4,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'account_status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'user_id');

            $familyId = DB::table('families')->insertGetId([
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'family_id');

            return [
                'user_id' => $userId,
                'family_id' => $familyId,
            ];
        });

        return response()->json([
            'message' => 'Registrasi keluarga berhasil.',
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

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'Email atau kata sandi salah'
            ], 401);
        }

        if ($user->account_status !== 'Aktif') {
            return response()->json([
                'message' => 'Akun belum aktif atau sedang menunggu verifikasi'
            ], 403);
        }

        $token = $user->createToken('diabetaku-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
