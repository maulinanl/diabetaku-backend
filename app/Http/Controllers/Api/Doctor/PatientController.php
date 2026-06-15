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
            ->where('dpr.status', 'Diterima')
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'p.diabetes_type',
                'dpr.connected_at'
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
                'created_at' => now(),
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
    public function medication($patientId)
    {
        $data = DB::table('medication_consumption_logs as mcl')
            ->leftJoin('prescriptions as p', 'mcl.prescription_id', '=', 'p.prescription_id')
            ->leftJoin('prescription_schedules as ps', 'mcl.schedule_id', '=', 'ps.schedule_id')
            ->where('mcl.patient_id', $patientId)
            ->select(
                'mcl.*',
                'p.drug_name',
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
}
