<?php

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    private function createNotification($userId, $typeId, $title, $message, $referenceId = null, $referenceType = null)
    {
        if (!$userId || !$typeId) return;

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

    private function getPatientName($patientId)
    {
        return DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.patient_id', $patientId)
            ->value('u.full_name') ?? 'Pasien';
    }

    public function findPatient(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'family_id' => 'required|exists:families,family_id',
        ]);

        $patients = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('family_patient_relations as fpr', function ($join) use ($request) {
                $join->on('p.patient_id', '=', 'fpr.patient_id')
                    ->where('fpr.family_id', '=', $request->family_id);
            })
            ->whereRaw('LOWER(u.email) = ?', [strtolower(trim($request->email))])
            ->select(
                'p.patient_id',
                'u.full_name',
                'u.email',
                'u.gender',
                'p.diabetes_type',
                'fpr.status',
                'fpr.connected_at'
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
            'family_id' => 'required|exists:families,family_id',
            'patient_id' => 'required|exists:patients,patient_id',
            'relation_type_id' => 'required|exists:relation_types,relation_type_id',
        ]);

        return DB::transaction(function () use ($request) {
            $existingRelation = DB::table('family_patient_relations')
                ->where('family_id', $request->family_id)
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
                DB::table('family_patient_relations')
                    ->where('family_id', $request->family_id)
                    ->where('patient_id', $request->patient_id)
                    ->update([
                        'relation_type_id' => $request->relation_type_id,
                        'status' => 'Menunggu',
                        'requested_at' => now(),
                        'responded_at' => null,
                        'connected_at' => null,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('family_patient_relations')->insert([
                    'family_id' => $request->family_id,
                    'patient_id' => $request->patient_id,
                    'relation_type_id' => $request->relation_type_id,
                    'status' => 'Menunggu',
                    'requested_at' => now(),
                    'responded_at' => null,
                    'connected_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $patientUserId = DB::table('patients')
                ->where('patient_id', $request->patient_id)
                ->value('user_id');

            $familyName = DB::table('families as f')
                ->join('users as u', 'f.user_id', '=', 'u.user_id')
                ->where('f.family_id', $request->family_id)
                ->value('u.full_name');

            $this->createNotification(
                $patientUserId,
                2,
                'Permintaan koneksi keluarga',
                ($familyName ?? 'Keluarga') . ' mengajukan permintaan koneksi sebagai pendamping.',
                $request->family_id,
                'family_request'
            );

            return response()->json([
                'message' => 'Permintaan koneksi ke pasien berhasil dikirim'
            ], 201);
        });
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
                'u.date_of_birth'
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

                'medication' => DB::table('medication_consumption_logs')
                    ->where('patient_id', $patientId)
                    ->orderByDesc('log_date')
                    ->get(),
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
            ->join('doctors as d', 'cn.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('cn.patient_id', $patientId)
            ->select(
                'r.recommendation_id',
                'r.recommendation_text',
                'r.category',
                'r.created_at',
                'cn.clinical_note_id',
                'cn.patient_id',
                'cn.doctor_id',
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
                WHEN r.role_name ILIKE '%family%' OR r.role_name ILIKE '%keluarga%' THEN 'Keluarga'
                WHEN r.role_name ILIKE '%patient%' OR r.role_name ILIKE '%pasien%' THEN 'Pasien'
                ELSE 'Pasien'
            END as input_by_role
        ";

        $glucose = DB::table('glucose_records as gr')
            ->leftJoin('users as iu', 'gr.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('gr.patient_id', $patientId)
            ->select(
                DB::raw("'Glukosa' as type"),
                DB::raw("CONCAT('Glukosa ', gr.measurement_type) as title"),
                DB::raw("TO_CHAR(gr.measured_at, 'DD Mon • HH24:MI') as time"),
                DB::raw("gr.glucose_value::text as value"),
                DB::raw("'mg/dL' as unit"),
                DB::raw("COALESCE(gr.validation_status::text, 'Valid') as status"),
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase),
                DB::raw("gr.measured_at as sort_date")
            );

        $physiological = DB::table('physiological_records as pr')
            ->leftJoin('users as iu', 'pr.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('pr.patient_id', $patientId)
            ->select(
                DB::raw("'Fisiologis' as type"),
                DB::raw("'Tekanan Darah' as title"),
                DB::raw("TO_CHAR(pr.measured_at, 'DD Mon • HH24:MI') as time"),
                DB::raw("CONCAT(COALESCE(pr.systolic::text, '-'), '/', COALESCE(pr.diastolic::text, '-')) as value"),
                DB::raw("'mmHg' as unit"),
                DB::raw("COALESCE(pr.validation_status::text, 'Valid') as status"),
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase),
                DB::raw("pr.measured_at as sort_date")
            );

        $activity = DB::table('activity_records as ar')
            ->leftJoin('activity_types as at', 'ar.activity_type_id', '=', 'at.activity_type_id')
            ->leftJoin('users as iu', 'ar.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('ar.patient_id', $patientId)
            ->select(
                DB::raw("'Aktivitas' as type"),
                DB::raw("COALESCE(at.activity_name, 'Aktivitas Fisik') as title"),
                DB::raw("TO_CHAR(ar.activity_date, 'DD Mon') as time"),
                DB::raw("ar.duration_minutes::text as value"),
                DB::raw("'menit' as unit"),
                DB::raw("COALESCE(ar.validation_status::text, 'Valid') as status"),
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase),
                DB::raw("ar.activity_date as sort_date")
            );

        $meal = DB::table('meal_records as mr')
            ->leftJoin('meal_types as mt', 'mr.meal_type_id', '=', 'mt.meal_type_id')
            ->leftJoin('users as iu', 'mr.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('mr.patient_id', $patientId)
            ->select(
                DB::raw("'Makan' as type"),
                DB::raw("COALESCE(mt.meal_type_name, 'Pola Makan') as title"),
                DB::raw("TO_CHAR(mr.meal_date, 'DD Mon') as time"),
                DB::raw("COALESCE(mr.calories::text, '-') as value"),
                DB::raw("'kkal' as unit"),
                DB::raw("COALESCE(mr.validation_status::text, 'Valid') as status"),
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase),
                DB::raw("mr.meal_date as sort_date")
            );

        $medication = DB::table('medication_consumption_logs as l')
            ->leftJoin('prescriptions as p', 'l.prescription_id', '=', 'p.prescription_id')
            ->leftJoin('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->leftJoin('users as iu', 'l.input_by_user_id', '=', 'iu.user_id')
            ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
            ->where('l.patient_id', $patientId)
            ->select(
                DB::raw("'Obat' as type"),
                DB::raw("COALESCE(m.medication_name, 'Kepatuhan Obat') as title"),
                DB::raw("TO_CHAR(l.log_date, 'DD Mon') as time"),
                DB::raw("l.status::text as value"),
                DB::raw("'' as unit"),
                DB::raw("COALESCE(l.validation_status::text, 'Valid') as status"),
                DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                DB::raw($roleCase),
                DB::raw("l.log_date as sort_date")
            );

        $data = $glucose
            ->unionAll($physiological)
            ->unionAll($activity)
            ->unionAll($meal)
            ->unionAll($medication);

        $histories = DB::query()
            ->fromSub($data, 'histories')
            ->orderByDesc('sort_date')
            ->get();

        return response()->json([
            'message' => 'Riwayat pasien berhasil diambil',
            'data' => $histories
        ]);
    }

    public function disconnect($patientId, Request $request)
    {
        $request->validate([
            'family_id' => 'required|exists:families,family_id',
        ]);

        return DB::transaction(function () use ($patientId, $request) {
            $updated = DB::table('family_patient_relations')
                ->where('family_id', $request->family_id)
                ->where('patient_id', $patientId)
                ->where('status', 'Diterima')
                ->update([
                    'status' => 'Diputus',
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

            $familyName = DB::table('families as f')
                ->join('users as u', 'f.user_id', '=', 'u.user_id')
                ->where('f.family_id', $request->family_id)
                ->value('u.full_name');

            $this->createNotification(
                $patientUserId,
                6,
                'Relasi keluarga terputus',
                'Relasi dengan ' . ($familyName ?? 'keluarga') . ' telah diputus.',
                $request->family_id,
                'family_disconnected'
            );

            return response()->json([
                'message' => 'Relasi keluarga dan pasien berhasil diputus'
            ]);
        });
    }
}
