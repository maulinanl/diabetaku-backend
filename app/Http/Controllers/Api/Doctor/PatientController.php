<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function index($doctorId)
    {
        $patients = DB::table('doctor_patient_relations as dpr')
            ->join('patients as p', 'dpr.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('dpr.doctor_id', $doctorId)
            ->whereIn('dpr.status', ['Diterima', 'Diputus'])
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'u.date_of_birth',
                'p.diabetes_type',
                'dpr.status as relation_status',
                'dpr.connected_at',
                'dpr.updated_at'
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Daftar pasien berhasil diambil',
            'data' => $patients
        ]);
    }

    public function dashboard($patientId)
    {
        $profile = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.patient_id', $patientId)
            ->select(
                'p.*',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'u.date_of_birth'
            )
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

    public function glucose($patientId)
    {
        return response()->json([
            'message' => 'Data glukosa berhasil diambil',
            'data' => DB::table('glucose_records')
                ->where('patient_id', $patientId)
                ->orderByDesc('measured_at')
                ->get()
        ]);
    }

    public function physiological($patientId)
    {
        return response()->json([
            'message' => 'Data fisiologis berhasil diambil',
            'data' => DB::table('physiological_records')
                ->where('patient_id', $patientId)
                ->orderByDesc('measured_at')
                ->get()
        ]);
    }

    public function behavioral($patientId)
    {
        $activities = DB::table('activity_records as ar')
            ->leftJoin('activity_types as at', 'ar.activity_type_id', '=', 'at.activity_type_id')
            ->where('ar.patient_id', $patientId)
            ->select('ar.*', 'at.activity_name')
            ->orderByDesc('ar.activity_date')
            ->get();

        $meals = DB::table('meal_records as mr')
            ->leftJoin('meal_types as mt', 'mr.meal_type_id', '=', 'mt.meal_type_id')
            ->where('mr.patient_id', $patientId)
            ->select('mr.*', 'mt.meal_type_name')
            ->orderByDesc('mr.meal_date')
            ->get();

        return response()->json([
            'message' => 'Data perilaku pasien berhasil diambil',
            'data' => [
                'activities' => $activities,
                'meals' => $meals,
            ]
        ]);
    }

    public function medication($patientId)
    {
        $data = DB::table('medication_consumption_logs as mcl')
            ->leftJoin('prescriptions as p', 'mcl.prescription_id', '=', 'p.prescription_id')
            ->leftJoin('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->leftJoin('prescription_schedules as ps', 'mcl.schedule_id', '=', 'ps.schedule_id')
            ->where('mcl.patient_id', $patientId)
            ->select(
                'mcl.*',
                'm.medication_name',
                'p.dosage',
                'p.form',
                'ps.session',
                'ps.dose_per_session'
            )
            ->orderByDesc('mcl.log_date')
            ->get();

        return response()->json([
            'message' => 'Data konsumsi obat berhasil diambil',
            'data' => $data
        ]);
    }

    public function thresholds($patientId)
    {
        $data = DB::table('clinical_parameters as cp')
            ->leftJoin('patient_custom_thresholds as pct', function ($join) use ($patientId) {
                $join->on('cp.parameter_id', '=', 'pct.parameter_id')
                    ->where('pct.patient_id', '=', $patientId);
            })
            ->select(
                'cp.parameter_id',
                'cp.parameter_name',
                'cp.default_min',
                'cp.default_max',
                'cp.unit',
                'pct.custom_min',
                'pct.custom_max',
                'pct.set_by_doctor_id'
            )
            ->orderBy('cp.parameter_id')
            ->get();

        return response()->json([
            'message' => 'Batas normal pasien berhasil diambil',
            'data' => $data
        ]);
    }

    public function updateThreshold(Request $request, $patientId, $parameterId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'custom_min' => 'nullable|numeric',
            'custom_max' => 'nullable|numeric',
        ]);

        DB::table('patient_custom_thresholds')->updateOrInsert(
            [
                'patient_id' => $patientId,
                'parameter_id' => $parameterId,
            ],
            [
                'set_by_doctor_id' => $request->doctor_id,
                'custom_min' => $request->custom_min,
                'custom_max' => $request->custom_max,
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Batas normal pasien berhasil diperbarui'
        ]);
    }

    public function resetThreshold($patientId, $parameterId)
    {
        DB::table('patient_custom_thresholds')
            ->where('patient_id', $patientId)
            ->where('parameter_id', $parameterId)
            ->delete();

        return response()->json([
            'message' => 'Batas normal pasien berhasil dikembalikan ke default'
        ]);
    }

    public function disconnect(Request $request, $patientId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
        ]);

        DB::table('doctor_patient_relations')
            ->where('doctor_id', $request->doctor_id)
            ->where('patient_id', $patientId)
            ->update([
                'status' => 'Diputus',
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Relasi dokter dan pasien berhasil diputus'
        ]);
    }

    public function families($patientId)
    {
        $families = DB::table('family_patient_relations as fpr')
            ->join('families as f', 'fpr.family_id', '=', 'f.family_id')
            ->join('users as u', 'f.user_id', '=', 'u.user_id')
            ->leftJoin('relation_types as rt', 'fpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('fpr.patient_id', $patientId)
            ->where('fpr.status', 'Diterima')
            ->select(
                'f.family_id',
                'u.user_id',
                'u.full_name',
                'u.email',
                'rt.relation_name'
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Daftar keluarga pasien berhasil diambil',
            'data' => $families
        ]);
    }

    public function connectionRequests($doctorId)
    {
        $requests = DB::table('doctor_patient_relations as dpr')
            ->join('patients as p', 'dpr.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('dpr.doctor_id', $doctorId)
            ->where('dpr.status', 'Menunggu')
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.gender',
                'u.date_of_birth',
                'p.diabetes_type',
                'dpr.requested_at'
            )
            ->orderByDesc('dpr.requested_at')
            ->get();

        return response()->json([
            'message' => 'Daftar permintaan koneksi berhasil diambil',
            'data' => $requests
        ]);
    }

    public function acceptConnection(Request $request, $patientId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
        ]);

        DB::table('doctor_patient_relations')
            ->where('doctor_id', $request->doctor_id)
            ->where('patient_id', $patientId)
            ->update([
                'status' => 'Diterima',
                'responded_at' => now(),
                'connected_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Permintaan koneksi berhasil diterima'
        ]);
    }

    public function rejectConnection(Request $request, $patientId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
        ]);

        DB::table('doctor_patient_relations')
            ->where('doctor_id', $request->doctor_id)
            ->where('patient_id', $patientId)
            ->update([
                'status' => 'Ditolak',
                'responded_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Permintaan koneksi berhasil ditolak'
        ]);
    }

    public function rejectedConnectionRequests($doctorId)
    {
        $requests = DB::table('doctor_patient_relations as dpr')
            ->join('patients as p', 'dpr.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('dpr.doctor_id', $doctorId)
            ->where('dpr.status', 'Ditolak')
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.gender',
                'u.date_of_birth',
                'p.diabetes_type',
                'dpr.requested_at',
                'dpr.responded_at'
            )
            ->orderByDesc('dpr.responded_at')
            ->get();

        return response()->json([
            'message' => 'Daftar koneksi ditolak berhasil diambil',
            'data' => $requests
        ]);
    }

    public function connectionStatus(Request $request, $patientId)
    {
        $doctorId = $request->query('doctor_id');

        $connection = DB::table('doctor_patient_relations as dpr')
            ->join('patients as p', 'dpr.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('dpr.patient_id', $patientId)
            ->where('dpr.doctor_id', $doctorId)
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.gender',
                'u.date_of_birth',
                'p.diabetes_type',
                'dpr.status'
            )
            ->first();

        if (!$connection) {
            return response()->json([
                'message' => 'Data koneksi tidak ditemukan',
                'data' => [
                    'patient_id' => $patientId,
                    'status_id' => 0,
                    'connection_status' => 'Menunggu persetujuan dokter'
                ]
            ]);
        }

        $statusId = match ($connection->status) {
            'Diterima', 'Aktif', 'accepted', 'active' => 1,
            'Ditolak', 'rejected' => 2,
            default => 0,
        };

        return response()->json([
            'message' => 'Status koneksi berhasil diambil',
            'data' => [
                'patient_id' => $connection->patient_id,
                'full_name' => $connection->full_name,
                'gender' => $connection->gender,
                'date_of_birth' => $connection->date_of_birth,
                'diabetes_type' => $connection->diabetes_type,
                'status_id' => $statusId,
                'status' => $connection->status,
            ]
        ]);
    }
}
