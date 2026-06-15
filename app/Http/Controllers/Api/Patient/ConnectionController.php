<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConnectionController extends Controller
{
    public function findDoctors(Request $request)
    {
        $keyword = $request->query('keyword');

        $doctors = DB::table('doctors as d')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->where('d.verification_status', 'Disetujui')
            ->where('u.account_status', 'Aktif')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('u.full_name', 'ILIKE', "%{$keyword}%")
                      ->orWhere('s.specialization_name', 'ILIKE', "%{$keyword}%")
                      ->orWhere('d.institution', 'ILIKE', "%{$keyword}%");
                });
            })
            ->select(
                'd.doctor_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                's.specialization_name',
                'd.str_number',
                'd.institution'
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Data dokter berhasil diambil',
            'data' => $doctors
        ]);
    }

    public function requestDoctor(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'doctor_id' => 'required|exists:doctors,doctor_id',
        ]);

        DB::table('doctor_patient_relations')->updateOrInsert(
            [
                'doctor_id' => $request->doctor_id,
                'patient_id' => $request->patient_id,
            ],
            [
                'status' => 'Menunggu',
                'requested_at' => now(),
                'responded_at' => null,
                'connected_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Permintaan koneksi ke dokter berhasil dikirim'
        ], 201);
    }

    public function doctors($patientId)
    {
        $doctors = DB::table('doctor_patient_relations as dpr')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->where('dpr.patient_id', $patientId)
            ->where('dpr.status', 'Diterima')
            ->select(
                'd.doctor_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                's.specialization_name',
                'd.institution',
                'dpr.connected_at'
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Daftar dokter terhubung berhasil diambil',
            'data' => $doctors
        ]);
    }

    public function doctorDetail($patientId, $doctorId)
    {
        $doctor = DB::table('doctor_patient_relations as dpr')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->where('dpr.patient_id', $patientId)
            ->where('dpr.doctor_id', $doctorId)
            ->select(
                'd.doctor_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                's.specialization_name',
                'd.str_number',
                'd.institution',
                'dpr.status',
                'dpr.connected_at'
            )
            ->first();

        if (!$doctor) {
            return response()->json([
                'message' => 'Data dokter tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail dokter berhasil diambil',
            'data' => $doctor
        ]);
    }

    public function disconnectDoctor(Request $request, $doctorId)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        DB::table('doctor_patient_relations')
            ->where('patient_id', $request->patient_id)
            ->where('doctor_id', $doctorId)
            ->where('status', 'Diterima')
            ->update([
                'status' => 'Diputus',
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Relasi dengan dokter berhasil diputus'
        ]);
    }
}
