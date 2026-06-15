<?php

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function findPatient(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);

        $patients = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('u.full_name', 'ILIKE', '%' . $request->keyword . '%')
            ->orWhere('u.email', 'ILIKE', '%' . $request->keyword . '%')
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'p.diabetes_type'
            )
            ->get();

        return response()->json([
            'message' => 'Data pasien berhasil ditemukan',
            'data' => $patients
        ]);
    }

    public function requestConnection(Request $request)
    {
        $request->validate([
            'family_id' => 'required|exists:families,family_id',
            'patient_id' => 'required|exists:patients,patient_id',
            'relation_type_id' => 'required|exists:relation_types,relation_type_id',
        ]);

        DB::table('family_patient_relations')->updateOrInsert(
            [
                'family_id' => $request->family_id,
                'patient_id' => $request->patient_id,
            ],
            [
                'relation_type_id' => $request->relation_type_id,
                'status' => 'Menunggu',
                'requested_at' => now(),
                'responded_at' => null,
                'connected_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Permintaan koneksi ke pasien berhasil dikirim'
        ], 201);
    }

    public function patients($familyId)
    {
        $patients = DB::table('family_patient_relations as fpr')
            ->join('patients as p', 'fpr.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('relation_types as rt', 'fpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('fpr.family_id', $familyId)
            ->where('fpr.status', 'Diterima')
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'p.diabetes_type',
                'rt.relation_name',
                'fpr.connected_at'
            )
            ->get();

        return response()->json([
            'message' => 'Daftar pasien terhubung berhasil diambil',
            'data' => $patients
        ]);
    }

    public function dashboard($patientId)
    {
        $profile = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.patient_id', $patientId)
            ->select('p.*', 'u.full_name', 'u.email', 'u.phone_number', 'u.gender', 'u.date_of_birth')
            ->first();

        $latestGlucose = DB::table('glucose_records')
            ->where('patient_id', $patientId)
            ->orderByDesc('measured_at')
            ->first();

        $latestPhysiological = DB::table('physiological_records')
            ->where('patient_id', $patientId)
            ->orderByDesc('measured_at')
            ->first();

        return response()->json([
            'message' => 'Dashboard pasien berhasil diambil',
            'data' => [
                'profile' => $profile,
                'latest_glucose' => $latestGlucose,
                'latest_physiological' => $latestPhysiological,
            ]
        ]);
    }

    public function healthData($patientId)
    {
        return response()->json([
            'message' => 'Data kesehatan pasien berhasil diambil',
            'data' => [
                'glucose' => DB::table('glucose_records')->where('patient_id', $patientId)->orderByDesc('measured_at')->get(),
                'physiological' => DB::table('physiological_records')->where('patient_id', $patientId)->orderByDesc('measured_at')->get(),
                'activity' => DB::table('activity_records')->where('patient_id', $patientId)->orderByDesc('activity_date')->get(),
                'meal' => DB::table('meal_records')->where('patient_id', $patientId)->orderByDesc('meal_date')->get(),
                'medication' => DB::table('medication_consumption_logs')->where('patient_id', $patientId)->orderByDesc('log_date')->get(),
            ]
        ]);
    }

    public function clinicalNotes($patientId)
    {
        return response()->json([
            'message' => 'Catatan klinis pasien berhasil diambil',
            'data' => DB::table('clinical_notes')
                ->where('patient_id', $patientId)
                ->orderByDesc('created_at')
                ->get()
        ]);
    }

    public function recommendations($patientId)
    {
        $data = DB::table('recommendations as r')
            ->join('clinical_notes as cn', 'r.clinical_note_id', '=', 'cn.clinical_note_id')
            ->where('cn.patient_id', $patientId)
            ->select('r.*', 'cn.patient_id', 'cn.doctor_id')
            ->orderByDesc('r.created_at')
            ->get();

        return response()->json([
            'message' => 'Rekomendasi pasien berhasil diambil',
            'data' => $data
        ]);
    }

    public function disconnect($patientId, Request $request)
    {
        $request->validate([
            'family_id' => 'required|exists:families,family_id',
        ]);

        DB::table('family_patient_relations')
            ->where('family_id', $request->family_id)
            ->where('patient_id', $patientId)
            ->where('status', 'Diterima')
            ->update([
                'status' => 'Diputus',
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Relasi keluarga dan pasien berhasil diputus'
        ]);
    }
}
