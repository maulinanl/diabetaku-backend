<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    private function createNotification($userId, $typeId, $title, $message, $referenceId = null, $referenceType = null)
    {
        if (!$userId) return;

        DB::table('notifications')->insert([
            'user_id' => $userId,
            'notification_type_id' => $typeId,
            'title' => $title,
            'message' => $message,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function abnormalConditionSql()
    {
        return "
            EXISTS (
                SELECT 1
                FROM glucose_records gr
                JOIN clinical_parameters cp
                    ON (
                        LOWER(cp.parameter_name) LIKE '%gula darah%'
                        OR LOWER(cp.parameter_name) LIKE '%glukosa%'
                    )
                    AND (
                        LOWER(cp.parameter_name) LIKE '%' || LOWER(gr.measurement_type::text) || '%'
                    )
                LEFT JOIN patient_custom_thresholds pct
                    ON pct.patient_id = gr.patient_id
                    AND pct.parameter_id = cp.parameter_id
                WHERE gr.patient_id = p.patient_id
                AND COALESCE(gr.validation_status, 'Valid') = 'Valid'
                AND (
                    gr.glucose_value < COALESCE(pct.custom_min, cp.default_min)
                    OR gr.glucose_value > COALESCE(pct.custom_max, cp.default_max)
                )
            )
            OR EXISTS (
                SELECT 1
                FROM physiological_records pr
                JOIN clinical_parameters cp
                    ON LOWER(cp.parameter_name) LIKE '%sistolik%'
                LEFT JOIN patient_custom_thresholds pct
                    ON pct.patient_id = pr.patient_id
                    AND pct.parameter_id = cp.parameter_id
                WHERE pr.patient_id = p.patient_id
                AND COALESCE(pr.validation_status, 'Valid') = 'Valid'
                AND pr.systolic IS NOT NULL
                AND (
                    pr.systolic < COALESCE(pct.custom_min, cp.default_min)
                    OR pr.systolic > COALESCE(pct.custom_max, cp.default_max)
                )
            )
            OR EXISTS (
                SELECT 1
                FROM physiological_records pr
                JOIN clinical_parameters cp
                    ON LOWER(cp.parameter_name) LIKE '%diastolik%'
                LEFT JOIN patient_custom_thresholds pct
                    ON pct.patient_id = pr.patient_id
                    AND pct.parameter_id = cp.parameter_id
                WHERE pr.patient_id = p.patient_id
                AND COALESCE(pr.validation_status, 'Valid') = 'Valid'
                AND pr.diastolic IS NOT NULL
                AND (
                    pr.diastolic < COALESCE(pct.custom_min, cp.default_min)
                    OR pr.diastolic > COALESCE(pct.custom_max, cp.default_max)
                )
            )
            OR EXISTS (
                SELECT 1
                FROM physiological_records pr
                JOIN clinical_parameters cp
                    ON (
                        LOWER(cp.parameter_name) LIKE '%bmi%'
                        OR LOWER(cp.parameter_name) LIKE '%imt%'
                    )
                LEFT JOIN patient_custom_thresholds pct
                    ON pct.patient_id = pr.patient_id
                    AND pct.parameter_id = cp.parameter_id
                WHERE pr.patient_id = p.patient_id
                AND COALESCE(pr.validation_status, 'Valid') = 'Valid'
                AND pr.bmi IS NOT NULL
                AND (
                    pr.bmi < COALESCE(pct.custom_min, cp.default_min)
                    OR pr.bmi > COALESCE(pct.custom_max, cp.default_max)
                )
            )
        ";
    }

    public function index($doctorId)
    {
        $abnormalSql = $this->abnormalConditionSql();

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
                'dpr.updated_at',
                DB::raw("CASE WHEN ($abnormalSql) THEN true ELSE false END as is_abnormal"),
                DB::raw("CASE WHEN ($abnormalSql) THEN 'abnormal' ELSE 'normal' END as status")
            )
            ->orderByRaw("
                CASE
                    WHEN dpr.status = 'Diterima' AND ($abnormalSql) THEN 1
                    WHEN dpr.status = 'Diterima' THEN 2
                    ELSE 3
                END
            ")
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

        return response()->json([
            'message' => 'Dashboard pasien berhasil diambil',
            'data' => [
                'profile' => $profile,
                'latest_glucose' => DB::table('glucose_records')
                    ->where('patient_id', $patientId)
                    ->orderByDesc('measured_at')
                    ->first(),
                'latest_physiological' => DB::table('physiological_records')
                    ->where('patient_id', $patientId)
                    ->orderByDesc('measured_at')
                    ->first(),
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

        return DB::transaction(function () use ($request, $patientId) {
            $updated = DB::table('doctor_patient_relations')
                ->where('doctor_id', $request->doctor_id)
                ->where('patient_id', $patientId)
                ->where('status', 'Diterima')
                ->update([
                    'status' => 'Diputus',
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return response()->json([
                    'message' => 'Relasi dokter dan pasien tidak ditemukan atau sudah diputus'
                ], 404);
            }

            $patientUserId = DB::table('patients')
                ->where('patient_id', $patientId)
                ->value('user_id');

            $doctorName = DB::table('doctors as d')
                ->join('users as u', 'd.user_id', '=', 'u.user_id')
                ->where('d.doctor_id', $request->doctor_id)
                ->value('u.full_name');

            $this->createNotification(
                $patientUserId,
                6,
                'Relasi dokter terputus',
                'Relasi dengan Dr. ' . ($doctorName ?? 'Dokter') . ' telah diputus.',
                $request->doctor_id,
                'doctor_connection_disconnected'
            );

            return response()->json([
                'message' => 'Relasi dokter dan pasien berhasil diputus'
            ]);
        });
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

        return DB::transaction(function () use ($request, $patientId) {
            $updated = DB::table('doctor_patient_relations')
                ->where('doctor_id', $request->doctor_id)
                ->where('patient_id', $patientId)
                ->where('status', 'Menunggu')
                ->update([
                    'status' => 'Diterima',
                    'responded_at' => now(),
                    'connected_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return response()->json([
                    'message' => 'Permintaan koneksi tidak ditemukan atau sudah diproses'
                ], 404);
            }

            $patientUserId = DB::table('patients')
                ->where('patient_id', $patientId)
                ->value('user_id');

            $doctorName = DB::table('doctors as d')
                ->join('users as u', 'd.user_id', '=', 'u.user_id')
                ->where('d.doctor_id', $request->doctor_id)
                ->value('u.full_name');

            $this->createNotification(
                $patientUserId,
                2,
                'Permintaan koneksi diterima',
                'Dr. ' . ($doctorName ?? 'Dokter') . ' menerima permintaan koneksi Anda.',
                $request->doctor_id,
                'doctor_connection_accepted'
            );

            return response()->json([
                'message' => 'Permintaan koneksi berhasil diterima'
            ]);
        });
    }

    public function rejectConnection(Request $request, $patientId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
        ]);

        return DB::transaction(function () use ($request, $patientId) {
            $updated = DB::table('doctor_patient_relations')
                ->where('doctor_id', $request->doctor_id)
                ->where('patient_id', $patientId)
                ->where('status', 'Menunggu')
                ->update([
                    'status' => 'Ditolak',
                    'responded_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return response()->json([
                    'message' => 'Permintaan koneksi tidak ditemukan atau sudah diproses'
                ], 404);
            }

            $patientUserId = DB::table('patients')
                ->where('patient_id', $patientId)
                ->value('user_id');

            $doctorName = DB::table('doctors as d')
                ->join('users as u', 'd.user_id', '=', 'u.user_id')
                ->where('d.doctor_id', $request->doctor_id)
                ->value('u.full_name');

            $this->createNotification(
                $patientUserId,
                2,
                'Permintaan koneksi ditolak',
                'Dr. ' . ($doctorName ?? 'Dokter') . ' menolak permintaan koneksi Anda.',
                $request->doctor_id,
                'doctor_connection_rejected'
            );

            return response()->json([
                'message' => 'Permintaan koneksi berhasil ditolak'
            ]);
        });
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

        if (!$doctorId) {
            return response()->json([
                'message' => 'doctor_id wajib dikirim'
            ], 422);
        }

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
                    'status' => 'Menunggu',
                    'connection_status' => 'Menunggu persetujuan dokter'
                ]
            ]);
        }

        $statusId = match ($connection->status) {
            'Diterima' => 1,
            'Ditolak' => 2,
            'Diputus' => 3,
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
