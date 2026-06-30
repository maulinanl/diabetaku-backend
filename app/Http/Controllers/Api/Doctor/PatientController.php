<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PrescriptionLifecycleService;

class PatientController extends Controller
{
    private function getNotificationTypeId($typeName)
    {
        return DB::table('notification_types')
            ->where('notification_type_name', $typeName)
            ->value('notification_type_id');
    }

    private function createNotification($userId, $typeName, $title, $message, $referenceId = null, $referenceType = null)
    {
        if (!$userId) return;

        $typeId = $this->getNotificationTypeId($typeName);
        if (!$typeId) return;

        $notificationId = DB::table('notifications')->insertGetId([
            'user_id' => $userId,
            'notification_type_id' => $typeId,
            'title' => $title,
            'message' => $message,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'notification_id');

        $sendPushNotification = function () use (
            $userId,
            $title,
            $message,
            $notificationId,
            $referenceId,
            $referenceType,
            $typeId
        ) {
            try {
                app(\App\Services\FcmService::class)->sendToUser(
                    $userId,
                    $title,
                    $message,
                    [
                        'notification_id' => $notificationId,
                        'reference_id' => $referenceId ?? '',
                        'reference_type' => $referenceType ?? '',
                        'notification_type_id' => $typeId,
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($sendPushNotification);
        } else {
            $sendPushNotification();
        }
    }

    private function abnormalConditionSql($doctorId)
    {
        $doctorId = (int) $doctorId;

        return "
            EXISTS (
                SELECT 1
                FROM glucose_records gr
                JOIN clinical_parameters cp
                    ON cp.parameter_name = CASE gr.measurement_type::text
                        WHEN 'Puasa' THEN 'Glukosa Puasa'
                        WHEN 'Dua Jam Setelah Makan' THEN 'Glukosa 2 Jam Setelah Makan'
                        WHEN 'Sewaktu' THEN 'Glukosa Sewaktu'
                        ELSE NULL
                    END
                LEFT JOIN patient_custom_thresholds pct
                    ON pct.doctor_patient_relation_id = dpr.doctor_patient_relation_id
                    AND pct.parameter_id = cp.parameter_id
                WHERE gr.patient_id = p.patient_id
                AND COALESCE(gr.validation_status, 'Valid') = 'Valid'
                AND gr.glucose_value IS NOT NULL
                AND gr.measured_at = (
                    SELECT MAX(gr2.measured_at)
                    FROM glucose_records gr2
                    WHERE gr2.patient_id = gr.patient_id
                    AND gr2.measurement_type = gr.measurement_type
                    AND COALESCE(gr2.validation_status, 'Valid') = 'Valid'
                )
                AND (
                    gr.glucose_value < COALESCE(pct.custom_min, cp.default_min)
                    OR gr.glucose_value > COALESCE(pct.custom_max, cp.default_max)
                )
            )

            OR EXISTS (
                SELECT 1
                FROM physiological_records pr
                JOIN clinical_parameters cp ON cp.parameter_name = 'Tekanan Darah Sistolik'
                LEFT JOIN patient_custom_thresholds pct
                    ON pct.doctor_patient_relation_id = dpr.doctor_patient_relation_id
                    AND pct.parameter_id = cp.parameter_id
                WHERE pr.patient_id = p.patient_id
                AND COALESCE(pr.validation_status, 'Valid') = 'Valid'
                AND pr.systolic IS NOT NULL
                AND pr.measured_at = (
                    SELECT MAX(pr2.measured_at)
                    FROM physiological_records pr2
                    WHERE pr2.patient_id = pr.patient_id
                    AND COALESCE(pr2.validation_status, 'Valid') = 'Valid'
                )
                AND (
                    pr.systolic < COALESCE(pct.custom_min, cp.default_min)
                    OR pr.systolic > COALESCE(pct.custom_max, cp.default_max)
                )
            )

            OR EXISTS (
                SELECT 1
                FROM physiological_records pr
                JOIN clinical_parameters cp ON cp.parameter_name = 'Tekanan Darah Diastolik'
                LEFT JOIN patient_custom_thresholds pct
                    ON pct.doctor_patient_relation_id = dpr.doctor_patient_relation_id
                    AND pct.parameter_id = cp.parameter_id
                WHERE pr.patient_id = p.patient_id
                AND COALESCE(pr.validation_status, 'Valid') = 'Valid'
                AND pr.diastolic IS NOT NULL
                AND pr.measured_at = (
                    SELECT MAX(pr2.measured_at)
                    FROM physiological_records pr2
                    WHERE pr2.patient_id = pr.patient_id
                    AND COALESCE(pr2.validation_status, 'Valid') = 'Valid'
                )
                AND (
                    pr.diastolic < COALESCE(pct.custom_min, cp.default_min)
                    OR pr.diastolic > COALESCE(pct.custom_max, cp.default_max)
                )
            )
        ";
    }

    public function index($doctorId)
    {
        $abnormalSql = $this->abnormalConditionSql($doctorId);

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
                'p.date_of_birth',
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
                'p.date_of_birth'
            )
            ->first();

        $latestPhysiological = DB::table('physiological_records')
            ->where('patient_id', $patientId)
            ->where('validation_status', 'Valid')
            ->orderByDesc('measured_at')
            ->first();

        if (
            $latestPhysiological &&
            $profile &&
            $profile->height_cm &&
            $latestPhysiological->weight_kg
        ) {
            $heightMeter = ((float) $profile->height_cm) / 100;

            if ($heightMeter > 0) {
                $latestPhysiological->bmi = round(
                    ((float) $latestPhysiological->weight_kg) / ($heightMeter * $heightMeter),
                    1
                );
            }
        }

        return response()->json([
            'message' => 'Dashboard pasien berhasil diambil',
            'data' => [
                'profile' => $profile,
                'latest_glucose' => DB::table('glucose_records')
                    ->where('patient_id', $patientId)
                    ->where('validation_status', 'Valid')
                    ->orderByDesc('measured_at')
                    ->first(),
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
                ->where('validation_status', 'Valid')
                ->orderByDesc('measured_at')
                ->get()
        ]);
    }

    public function physiological($patientId)
    {
        $heightCm = DB::table('patients')
            ->where('patient_id', $patientId)
            ->value('height_cm');

        $data = DB::table('physiological_records')
            ->where('patient_id', $patientId)
            ->where('validation_status', 'Valid')
            ->orderByDesc('measured_at')
            ->get()
            ->map(function ($item) use ($heightCm) {
                if ($heightCm && $item->weight_kg) {
                    $heightMeter = ((float) $heightCm) / 100;

                    if ($heightMeter > 0) {
                        $item->bmi = round(((float) $item->weight_kg) / ($heightMeter * $heightMeter), 1);
                    }
                }

                return $item;
            });

        return response()->json([
            'message' => 'Data fisiologis berhasil diambil',
            'data' => $data
        ]);
    }

    public function behavioral($patientId)
    {
        $activities = DB::table('activity_records as ar')
            ->leftJoin('activity_types as at', 'ar.activity_type_id', '=', 'at.activity_type_id')
            ->where('ar.patient_id', $patientId)
            ->where('ar.validation_status', 'Valid')
            ->select('ar.*', 'at.activity_name')
            ->orderByDesc('ar.activity_date')
            ->get();

        $meals = DB::table('meal_records as mr')
            ->leftJoin('meal_types as mt', 'mr.meal_type_id', '=', 'mt.meal_type_id')
            ->where('mr.patient_id', $patientId)
            ->where('mr.validation_status', 'Valid')
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
            ->leftJoin('prescription_schedules as ps', 'mcl.prescription_schedule_id', '=', 'ps.prescription_schedule_id')
            ->leftJoin('prescriptions as p', 'ps.prescription_id', '=', 'p.prescription_id')
            ->leftJoin('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->leftJoin('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->leftJoin('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
            ->where('dpr.patient_id', $patientId)
            ->where('mcl.validation_status', 'Valid')
            ->select(
                'mcl.log_id',
                'dpr.patient_id',
                'p.prescription_id',
                'ps.prescription_schedule_id as schedule_id',
                'ps.prescription_schedule_id',
                'mcl.input_by_user_id',
                'mcl.log_date',
                'mcl.status',
                'mcl.taken_at',
                'mcl.note',
                'mcl.validation_status',
                'm.medication_name',
                DB::raw("TRIM(COALESCE(p.quantity::text, '') || ' ' || COALESCE(p.quantity_unit, '')) as dosage"),
                'm.dosage_form as form',
                'p.meal_rule',
                'p.notes',
                'ms.session_name',
                'ms.start_time',
                'ms.end_time',
                'ms.default_reminder_time',
                DB::raw('ms.default_reminder_time as reminder_time'),
                DB::raw("TRIM(COALESCE(p.quantity::text, '') || ' ' || COALESCE(p.quantity_unit, '')) as dose_per_session")
            )
            ->orderByDesc('mcl.log_date')
            ->orderBy('ms.start_time')
            ->get();

        return response()->json([
            'message' => 'Data konsumsi obat berhasil diambil',
            'data' => $data
        ]);
    }

    private function getDoctorPatientRelationId(
        int $doctorId,
        int $patientId,
        array $statuses = ['Diterima']
    ): ?int {
        $id = DB::table('doctor_patient_relations')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->whereIn('status', $statuses)
            ->orderByRaw("CASE WHEN status = 'Diterima' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->value('doctor_patient_relation_id');

        return $id === null ? null : (int) $id;
    }

    public function thresholds(Request $request, $patientId)
    {
        $doctorId = $request->query('doctor_id');

        if (!$doctorId) {
            return response()->json([
                'message' => 'doctor_id wajib dikirim'
            ], 422);
        }

        $relationId = $this->getDoctorPatientRelationId((int) $doctorId, (int) $patientId, ['Diterima', 'Diputus']);

        $data = DB::table('clinical_parameters as cp')
            ->leftJoin('patient_custom_thresholds as pct', function ($join) use ($relationId) {
                $join->on('cp.parameter_id', '=', 'pct.parameter_id')
                    ->where('pct.doctor_patient_relation_id', '=', $relationId);
            })
            ->select(
                'cp.parameter_id',
                'cp.parameter_name',
                'cp.default_min',
                'cp.default_max',
                'cp.valid_min',
                'cp.valid_max',
                'cp.unit',
                'pct.custom_min',
                'pct.custom_max',
                'pct.doctor_patient_relation_id'
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
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'custom_min' => 'required|numeric',
            'custom_max' => 'required|numeric',
        ]);

        $relationId = $this->getDoctorPatientRelationId((int) $validated['doctor_id'], (int) $patientId);

        if (!$relationId) {
            return response()->json([
                'message' => 'Relasi dokter dan pasien aktif tidak ditemukan'
            ], 404);
        }

        $parameter = DB::table('clinical_parameters')
            ->where('parameter_id', $parameterId)
            ->first();

        if (!$parameter) {
            return response()->json([
                'message' => 'Parameter klinis tidak ditemukan'
            ], 404);
        }

        $customMin = (float) $validated['custom_min'];
        $customMax = (float) $validated['custom_max'];
        $unit = $parameter->unit ? ' ' . $parameter->unit : '';

        if ($customMax <= $customMin) {
            return response()->json([
                'message' => 'Batas atas harus lebih besar dari batas bawah'
            ], 422);
        }

        if ($parameter->valid_min !== null && $customMin < (float) $parameter->valid_min) {
            return response()->json([
                'message' => 'Batas bawah ' . $parameter->parameter_name . ' tidak boleh kurang dari ' . $parameter->valid_min . $unit
            ], 422);
        }

        if ($parameter->valid_max !== null && $customMax > (float) $parameter->valid_max) {
            return response()->json([
                'message' => 'Batas atas ' . $parameter->parameter_name . ' tidak boleh lebih dari ' . $parameter->valid_max . $unit
            ], 422);
        }

        $exists = DB::table('patient_custom_thresholds')
            ->where('doctor_patient_relation_id', $relationId)
            ->where('parameter_id', $parameterId)
            ->exists();

        if ($exists) {
            DB::table('patient_custom_thresholds')
                ->where('doctor_patient_relation_id', $relationId)
                ->where('parameter_id', $parameterId)
                ->update([
                    'custom_min' => $customMin,
                    'custom_max' => $customMax,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('patient_custom_thresholds')->insert([
                'doctor_patient_relation_id' => $relationId,
                'parameter_id' => $parameterId,
                'custom_min' => $customMin,
                'custom_max' => $customMax,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Batas normal pasien berhasil diperbarui'
        ]);
    }

    public function resetThreshold(Request $request, $patientId, $parameterId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
        ]);

        $relationId = $this->getDoctorPatientRelationId((int) $request->doctor_id, (int) $patientId);

        if (!$relationId) {
            return response()->json([
                'message' => 'Relasi dokter dan pasien aktif tidak ditemukan'
            ], 404);
        }

        DB::table('patient_custom_thresholds')
            ->where('doctor_patient_relation_id', $relationId)
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
                    'disconnected_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return response()->json([
                    'message' => 'Relasi dokter dan pasien tidak ditemukan atau sudah diputus'
                ], 404);
            }

            app(PrescriptionLifecycleService::class)
                ->finishActivePrescriptionsForRelation(
                    (int) $request->doctor_id,
                    (int) $patientId
                );

            $patientUserId = DB::table('patients')
                ->where('patient_id', $patientId)
                ->value('user_id');

            $doctorName = DB::table('doctors as d')
                ->join('users as u', 'd.user_id', '=', 'u.user_id')
                ->where('d.doctor_id', $request->doctor_id)
                ->value('u.full_name');

            $this->createNotification(
                $patientUserId,
                'Putus Relasi',
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

    public function caregivers($patientId)
    {
        $caregivers = DB::table('caregiver_patient_relations as cpr')
            ->join('caregivers as c', 'cpr.caregiver_id', '=', 'c.caregiver_id')
            ->join('users as u', 'c.user_id', '=', 'u.user_id')
            ->leftJoin('relation_types as rt', 'cpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('cpr.patient_id', $patientId)
            ->where('cpr.status', 'Diterima')
            ->select(
                'c.caregiver_id',
                DB::raw('c.caregiver_id as caregiver_id'),
                'u.user_id',
                'u.full_name',
                'u.email',
                'rt.relation_name'
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Daftar keluarga pasien berhasil diambil',
            'data' => $caregivers
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
                'p.date_of_birth',
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
                'Permintaan Koneksi',
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
                'Permintaan Koneksi',
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
                'p.date_of_birth',
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
                'p.date_of_birth',
                'p.diabetes_type',
                'dpr.status'
            )
            ->first();

        if (!$connection) {
            return response()->json([
                'message' => 'Data koneksi tidak ditemukan',
                'data' => [
                    'patient_id' => (int) $patientId,
                    'status_id' => 0,
                    'status' => 'Belum Terhubung',
                    'connection_status' => 'Belum ada relasi dengan dokter ini'
                ]
            ]);
        }

        $statusId = match ($connection->status) {
            'Diterima' => 1,
            'Ditolak' => 2,
            'Diputus' => 3,
            'Menunggu' => 4,
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
