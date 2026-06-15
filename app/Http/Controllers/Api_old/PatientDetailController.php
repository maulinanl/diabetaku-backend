<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PatientDetailController extends Controller
{
    public function show($patientId)
    {
        $patient = DB::table('patients as p')
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
                'bt.blood_type',
                'rt.rhesus_type'
            )
            ->first();

        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan'
            ], 404);
        }

        $latestGlucose = DB::table('glucose_records')
            ->where('patient_id', $patientId)
            ->orderByDesc('measured_at')
            ->first();

        $latestPhysiological = DB::table('physiological_records')
            ->where('patient_id', $patientId)
            ->orderByDesc('measured_at')
            ->first();

        return response()->json([
            'message' => 'Detail pasien berhasil diambil',
            'data' => [
                'profile' => $patient,
                'latest_glucose' => $latestGlucose,
                'latest_physiological' => $latestPhysiological
            ]
        ]);
    }
}
