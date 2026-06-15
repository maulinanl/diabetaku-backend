<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show($doctorId)
    {
        $profile = DB::table('doctors as d')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->where('d.doctor_id', $doctorId)
            ->select(
                'd.doctor_id',
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.date_of_birth',
                'u.gender',
                'd.specialization_id',
                's.specialization_name',
                'd.str_number',
                'd.institution',
                'd.verification_status'
            )
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profil dokter tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Profil dokter berhasil diambil',
            'data' => $profile
        ]);
    }

    public function update(Request $request, $doctorId)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'specialization_id' => 'required|exists:specializations,specialization_id',
            'str_number' => 'required|string|max:50',
            'institution' => 'nullable|string|max:200',
        ]);

        $doctor = DB::table('doctors')
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$doctor) {
            return response()->json([
                'message' => 'Profil dokter tidak ditemukan'
            ], 404);
        }

        DB::transaction(function () use ($request, $doctor, $doctorId) {
            DB::table('users')
                ->where('user_id', $doctor->user_id)
                ->update([
                    'full_name' => $request->full_name,
                    'phone_number' => $request->phone_number,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    'updated_at' => now(),
                ]);

            DB::table('doctors')
                ->where('doctor_id', $doctorId)
                ->update([
                    'specialization_id' => $request->specialization_id,
                    'str_number' => $request->str_number,
                    'institution' => $request->institution,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'message' => 'Profil dokter berhasil diperbarui'
        ]);
    }
}
