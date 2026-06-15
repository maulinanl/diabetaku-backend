<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show($patientId)
    {
        $profile = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('blood_types as bt', 'p.blood_type_id', '=', 'bt.blood_type_id')
            ->leftJoin('rhesus_types as rt', 'p.rhesus_type_id', '=', 'rt.rhesus_type_id')
            ->where('p.patient_id', $patientId)
            ->select(
                'p.patient_id',
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.date_of_birth',
                'u.gender',
                'p.diabetes_type',
                'p.diagnosis_date',
                'p.height_cm',
                'p.blood_type_id',
                'bt.blood_type',
                'p.rhesus_type_id',
                'rt.rhesus_type'
            )
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profil pasien tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Profil pasien berhasil diambil',
            'data' => $profile
        ]);
    }

    public function update(Request $request, $patientId)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'diabetes_type' => 'required|in:Tipe 1,Tipe 2',
            'diagnosis_date' => 'nullable|date',
            'height_cm' => 'nullable|numeric',
            'blood_type_id' => 'nullable|exists:blood_types,blood_type_id',
            'rhesus_type_id' => 'nullable|exists:rhesus_types,rhesus_type_id',
        ]);

        $patient = DB::table('patients')
            ->where('patient_id', $patientId)
            ->first();

        if (!$patient) {
            return response()->json([
                'message' => 'Profil pasien tidak ditemukan'
            ], 404);
        }

        DB::transaction(function () use ($request, $patient, $patientId) {
            DB::table('users')
                ->where('user_id', $patient->user_id)
                ->update([
                    'full_name' => $request->full_name,
                    'phone_number' => $request->phone_number,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    'updated_at' => now(),
                ]);

            DB::table('patients')
                ->where('patient_id', $patientId)
                ->update([
                    'diabetes_type' => $request->diabetes_type,
                    'diagnosis_date' => $request->diagnosis_date,
                    'height_cm' => $request->height_cm,
                    'blood_type_id' => $request->blood_type_id,
                    'rhesus_type_id' => $request->rhesus_type_id,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'message' => 'Profil pasien berhasil diperbarui'
        ]);
    }
}
