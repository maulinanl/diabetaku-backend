<?php

namespace App\Http\Controllers\Api\Caregiver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        if (!$userId || !$typeName) return;

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

    private function getPatientName($patientId)
    {
        return DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.patient_id', $patientId)
            ->value('u.full_name') ?? 'Pasien';
    }

    public function findPatient(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|max:150',
            'caregiver_id' => 'required|exists:caregivers,caregiver_id',
        ]);

        $keyword = strtolower(trim($validated['email']));

        if ($keyword === '' || !str_contains($keyword, '@')) {
            return response()->json([
                'message' => 'Lengkapi email pasien terlebih dahulu',
                'data' => [],
            ]);
        }

        $patients = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('caregiver_patient_relations as cpr', function ($join) use ($validated) {
                $join->on('p.patient_id', '=', 'cpr.patient_id')
                    ->where('cpr.caregiver_id', '=', $validated['caregiver_id']);
            })
            ->whereRaw('LOWER(TRIM(u.email)) = ?', [$keyword])
            ->where('u.role_id', 3)
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.email',
                'u.gender',
                'p.diabetes_type',
                'cpr.status',
                'cpr.connected_at'
            )
            ->get();

        return response()->json([
            'message' => 'Data pasien berhasil dicari',
            'data' => $patients,
        ]);
    }

    public function requestConnection(Request $request)
    {
        $request->validate([
            'caregiver_id' => 'required|exists:caregivers,caregiver_id',
            'patient_id' => 'required|exists:patients,patient_id',
            'relation_type_id' => 'required|exists:relation_types,relation_type_id',
        ]);

        return DB::transaction(function () use ($request) {
            $existingRelation = DB::table('caregiver_patient_relations')
                ->where('caregiver_id', $request->caregiver_id)
                ->where('patient_id', $request->patient_id)
                ->first();

            if ($existingRelation && $existingRelation->status === 'Diterima') {
                return response()->json([
                    'message' => 'Keluarga sudah terhubung dengan pasien ini'
                ], 409);
            }

            if ($existingRelation && $existingRelation->status === 'Menunggu') {
                return response()->json([
                    'message' => 'Permintaan koneksi sudah dikirim dan menunggu persetujuan pasien'
                ], 409);
            }

            if ($existingRelation) {
                DB::table('caregiver_patient_relations')
                    ->where('caregiver_id', $request->caregiver_id)
                    ->where('patient_id', $request->patient_id)
                    ->update([
                        'relation_type_id' => $request->relation_type_id,
                        'status' => 'Menunggu',
                        'requested_at' => now(),
                        'responded_at' => null,
                        'connected_at' => null,
                        'disconnected_at' => null,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('caregiver_patient_relations')->insert([
                    'caregiver_id' => $request->caregiver_id,
                    'patient_id' => $request->patient_id,
                    'relation_type_id' => $request->relation_type_id,
                    'status' => 'Menunggu',
                    'requested_at' => now(),
                    'responded_at' => null,
                    'connected_at' => null,
                    'disconnected_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $patientUserId = DB::table('patients')
                ->where('patient_id', $request->patient_id)
                ->value('user_id');

            $caregiverName = DB::table('caregivers as f')
                ->join('users as u', 'f.user_id', '=', 'u.user_id')
                ->where('f.caregiver_id', $request->caregiver_id)
                ->value('u.full_name');

            $this->createNotification(
                $patientUserId,
                'Permintaan Koneksi',
                'Permintaan koneksi keluarga',
                ($caregiverName ?? 'Keluarga') . ' mengajukan permintaan koneksi sebagai pendamping.',
                $request->caregiver_id,
                'caregiver_connection_request'
            );

            return response()->json([
                'message' => 'Permintaan koneksi ke pasien berhasil dikirim'
            ], 201);
        });
    }

    public function patients($caregiverId)
    {
        $patients = DB::table('caregiver_patient_relations as fpr')
            ->join('patients as p', 'fpr.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('relation_types as rt', 'fpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('fpr.caregiver_id', $caregiverId)
            ->where('fpr.status', 'Diterima')
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'p.diabetes_type',
                'rt.relation_name',
                'fpr.status',
                'fpr.connected_at'
            )
            ->orderBy('u.full_name')
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
            ->select(
                'p.*',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'p.date_of_birth'
            )
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan'
            ], 404);
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
                'latest_physiological' => DB::table('physiological_records')
                    ->where('patient_id', $patientId)
                    ->where('validation_status', 'Valid')
                    ->orderByDesc('measured_at')
                    ->first(),
            ]
        ]);
    }

    public function healthData($patientId)
    {
        return response()->json([
            'message' => 'Data kesehatan pasien berhasil diambil',
            'data' => [
                'glucose' => DB::table('glucose_records')
                    ->where('patient_id', $patientId)
                    ->orderByDesc('measured_at')
                    ->get(),

                'physiological' => DB::table('physiological_records')
                    ->where('patient_id', $patientId)
                    ->orderByDesc('measured_at')
                    ->get(),

                'activity' => DB::table('activity_records')
                    ->where('patient_id', $patientId)
                    ->orderByDesc('activity_date')
                    ->get(),

                'meal' => DB::table('meal_records')
                    ->where('patient_id', $patientId)
                    ->orderByDesc('meal_date')
                    ->get(),

                'medication' => DB::table('medication_consumption_logs as mcl')
                    ->join('prescription_schedules as ps', 'mcl.prescription_schedule_id', '=', 'ps.prescription_schedule_id')
                    ->join('prescriptions as prx', 'ps.prescription_id', '=', 'prx.prescription_id')
                    ->join('doctor_patient_relations as dpr', 'prx.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
                    ->where('dpr.patient_id', $patientId)
                    ->orderByDesc('mcl.log_date')
                    ->select('mcl.*', 'prx.prescription_id')
                    ->get(),
            ]
        ]);
    }

    public function clinicalNotes($patientId)
    {
        $data = DB::table('clinical_notes as cn')
            ->join('doctor_patient_relations as dpr', 'cn.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('dpr.patient_id', $patientId)
            ->select(
                'cn.*',
                'dpr.patient_id',
                'dpr.doctor_id',
                'u.full_name as doctor_name'
            )
            ->orderByDesc('cn.created_at')
            ->get();

        return response()->json([
            'message' => 'Catatan klinis pasien berhasil diambil',
            'data' => $data
        ]);
    }

    public function recommendations($patientId)
    {
        $data = DB::table('recommendations as r')
            ->join('clinical_notes as cn', 'r.clinical_note_id', '=', 'cn.clinical_note_id')
            ->join('doctor_patient_relations as dpr', 'cn.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('dpr.patient_id', $patientId)
            ->select(
                'r.recommendation_id',
                'r.recommendation_text',
                'r.category',
                'r.created_at',
                'cn.clinical_note_id',
                'dpr.patient_id',
                'dpr.doctor_id',
                'u.full_name as doctor_name'
            )
            ->orderByDesc('r.created_at')
            ->get();

        return response()->json([
            'message' => 'Rekomendasi pasien berhasil diambil',
            'data' => $data
        ]);
    }

    public function histories($patientId)
    {
        $roleCase = "
            CASE
                WHEN r.role_name ILIKE '%caregiver%' OR r.role_name ILIKE '%keluarga%' THEN 'Keluarga'
                WHEN r.role_name ILIKE '%patient%' OR r.role_name ILIKE '%pasien%' THEN 'Pasien'
                ELSE 'Pasien'
            END as input_by_role
        ";

        $glucose = DB::table('glucose_records as gr')
            ->leftJoin('users as iu', 'gr.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('gr.patient_id', $patientId)
            ->orderByDesc('gr.measured_at')
            ->select(
                'gr.*',
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase)
            )
            ->get();

        $physiological = DB::table('physiological_records as pr')
            ->leftJoin('users as iu', 'pr.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('pr.patient_id', $patientId)
            ->orderByDesc('pr.measured_at')
            ->select(
                'pr.*',
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase)
            )
            ->get();

        $activity = DB::table('activity_records as ar')
            ->leftJoin('activity_types as at', 'ar.activity_type_id', '=', 'at.activity_type_id')
            ->leftJoin('users as iu', 'ar.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('ar.patient_id', $patientId)
            ->orderByDesc('ar.activity_date')
            ->select(
                'ar.*',
                DB::raw("COALESCE(at.activity_name, 'Aktivitas Fisik') as activity_name"),
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase)
            )
            ->get();

        $meal = DB::table('meal_records as mr')
            ->leftJoin('meal_types as mt', 'mr.meal_type_id', '=', 'mt.meal_type_id')
            ->leftJoin('users as iu', 'mr.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('mr.patient_id', $patientId)
            ->orderByDesc('mr.meal_date')
            ->select(
                'mr.*',
                DB::raw("COALESCE(mt.meal_type_name, 'Pola Makan') as meal_type_name"),
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase)
            )
            ->get();

        $medication = DB::table('medication_consumption_logs as l')
            ->leftJoin('prescription_schedules as ps', 'l.prescription_schedule_id', '=', 'ps.prescription_schedule_id')
            ->leftJoin('prescriptions as p', 'ps.prescription_id', '=', 'p.prescription_id')
            ->leftJoin('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->leftJoin('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->leftJoin('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
            ->leftJoin('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->leftJoin('users as du', 'd.user_id', '=', 'du.user_id')
            ->leftJoin('users as iu', 'l.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('dpr.patient_id', $patientId)
            ->orderByDesc('l.log_date')
            ->select(
                'l.*',
                'p.prescription_id',
                DB::raw("COALESCE(m.medication_name, 'Obat') as medication_name"),
                DB::raw("COALESCE(du.full_name, '-') as doctor_name"),
                DB::raw("COALESCE(ms.session_name, ps.session_id::text, '-') as session"),
                DB::raw("TRIM(COALESCE(p.quantity::text, '-') || ' ' || COALESCE(p.quantity_unit, '')) as dose_per_session"),
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase)
            )
            ->get();

        return response()->json([
            'message' => 'Riwayat pasien berhasil diambil',
            'data' => [
                'glucose' => $glucose,
                'physiological' => $physiological,
                'activity' => $activity,
                'meal' => $meal,
                'medication' => $medication,
            ],
        ]);
    }


    public function activePrescriptions($patientId)
    {
        $today = now()->toDateString();

        $data = DB::table('prescriptions as p')
            ->join('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->join('prescription_schedules as ps', 'p.prescription_id', '=', 'ps.prescription_id')
            ->join('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
            ->leftJoin('medication_consumption_logs as l', function ($join) use ($today) {
                $join->on('l.prescription_schedule_id', '=', 'ps.prescription_schedule_id')
                    ->whereDate('l.log_date', '=', $today);
            })
            ->where('dpr.patient_id', $patientId)
            ->where('p.status_prescription', 'Aktif')
            ->whereDate('p.start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('p.end_date')
                    ->orWhereDate('p.end_date', '>=', $today);
            })
            ->where('ms.is_active', true)
            ->select(
                'p.prescription_id',
                'dpr.patient_id',
                'p.start_date',
                'p.end_date',
                'p.status_prescription',
                'p.quantity',
                'p.quantity_unit',
                'p.meal_rule',
                'p.notes',
                'ps.prescription_schedule_id',
                'm.medication_name',
                'm.dosage_form',
                'm.value',
                'm.unit',
                'm.description',
                'ms.session_id',
                'ms.session_name',
                'ms.start_time',
                'ms.end_time',
                'ms.default_reminder_time',
                'l.log_id',
                'l.status as log_status',
                'l.log_date',
                'l.taken_at',
                DB::raw("TRIM(COALESCE(p.quantity::text, '-') || ' ' || COALESCE(p.quantity_unit, '')) as dosage"),
                DB::raw("TRIM(COALESCE(p.quantity::text, '-') || ' ' || COALESCE(p.quantity_unit, '')) as dose_per_session"),
                DB::raw('ms.default_reminder_time as reminder_time'),
                DB::raw("CASE WHEN l.status = 'Diminum' THEN true ELSE false END as checked"),
                DB::raw("CASE WHEN l.log_id IS NULL THEN false ELSE true END as already_logged")
            )
            ->orderBy('ms.default_reminder_time')
            ->orderBy('m.medication_name')
            ->get();

        return response()->json([
            'message' => 'Resep aktif pasien berhasil diambil',
            'data' => $data,
        ]);
    }

    public function disconnect($patientId, Request $request)
    {
        $request->validate([
            'caregiver_id' => 'required|exists:caregivers,caregiver_id',
        ]);

        return DB::transaction(function () use ($patientId, $request) {
            $updated = DB::table('caregiver_patient_relations')
                ->where('caregiver_id', $request->caregiver_id)
                ->where('patient_id', $patientId)
                ->where('status', 'Diterima')
                ->update([
                    'status' => 'Diputus',
                    'disconnected_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return response()->json([
                    'message' => 'Relasi keluarga dan pasien tidak ditemukan atau sudah diputus'
                ], 404);
            }

            $patientUserId = DB::table('patients')
                ->where('patient_id', $patientId)
                ->value('user_id');

            $caregiverName = DB::table('caregivers as f')
                ->join('users as u', 'f.user_id', '=', 'u.user_id')
                ->where('f.caregiver_id', $request->caregiver_id)
                ->value('u.full_name');

            $this->createNotification(
                $patientUserId,
                'Putus Relasi',
                'Relasi keluarga terputus',
                'Relasi dengan ' . ($caregiverName ?? 'keluarga') . ' telah diputus.',
                $request->caregiver_id,
                'caregiver_connection_disconnected'
            );

            return response()->json([
                'message' => 'Relasi keluarga dan pasien berhasil diputus'
            ]);
        });
    }

    public function show($patientId)
    {
        $patient = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('blood_types as bt', 'p.blood_type_id', '=', 'bt.blood_type_id')
            ->leftJoin('rhesus_types as rt', 'p.rhesus_type_id', '=', 'rt.rhesus_type_id')
            ->where('p.patient_id', $patientId)
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.gender',
                'p.date_of_birth',
                'p.diabetes_type',
                'p.diagnosis_date',
                'p.height_cm',
                'bt.blood_type',
                'rt.rhesus_type'
            )
            ->first();

        if (!$patient) {
            return response()->json([
                'message' => 'Pasien tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pasien berhasil diambil',
            'data' => $patient,
        ]);
    }
}
