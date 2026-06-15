<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DoctorPatientController extends Controller
{
    public function getPatientsByDoctor($doctorId)
    {
        $patients = DB::table('doctor_patient_relations as dpr')
            ->join('patients as p', 'dpr.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('blood_types as bt', 'p.blood_type_id', '=', 'bt.blood_type_id')
            ->leftJoin('rhesus_types as rt', 'p.rhesus_type_id', '=', 'rt.rhesus_type_id')
            ->where('dpr.doctor_id', $doctorId)
            ->where('dpr.status', 'Diterima')
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'u.date_of_birth',
                'p.diabetes_type',
                'p.diagnosis_date',
                'p.height_cm',
                'bt.blood_type',
                'rt.rhesus_type'
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Daftar pasien berhasil diambil',
            'data' => $patients
        ]);
    }
}
