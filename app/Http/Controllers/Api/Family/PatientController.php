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
            'email' => 'required|email',
            'family_id' => 'required|exists:families,family_id',
        ]);

        $patients = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('family_patient_relations as fpr', function ($join) use ($request) {
                $join->on('p.patient_id', '=', 'fpr.patient_id')
                    ->where('fpr.family_id', '=', $request->family_id);
            })
            ->whereRaw('LOWER(u.email) = ?', [
                strtolower(trim($request->email)),
            ])
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
                    'created_at' => $existingRelation?->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            $patient = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $request->patient_id)
                ->select('p.patient_id', 'u.user_id', 'u.full_name')
                ->first();

            $family = DB::table('families as f')
                ->join('users as u', 'f.user_id', '=', 'u.user_id')
                ->where('f.family_id', $request->family_id)
                ->select('f.family_id', 'u.user_id', 'u.full_name')
                ->first();

            $typeId = DB::table('notification_types')
                ->where('notification_type_name', 'Permintaan Koneksi')
                ->value('notification_type_id');

            if ($patient && $family && $typeId) {
                DB::table('notifications')->insert([
                    'user_id' => $patient->user_id,
                    'notification_type_id' => $typeId,
                    'title' => 'Permintaan koneksi keluarga',
                    'message' => $family->full_name . ' mengajukan permintaan untuk terhubung sebagai keluarga pendamping.',
                    'reference_id' => $request->family_id,
                    'reference_type' => 'family_request',
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
        $glucose = DB::table('glucose_records')
            ->where('patient_id', $patientId)
            ->select(
                DB::raw("'Glukosa' as type"),
                DB::raw("CONCAT('Glukosa ', measurement_type) as title"),
                DB::raw("TO_CHAR(measured_at, 'DD Mon • HH24:MI') as time"),
                DB::raw("glucose_value::text as value"),
                DB::raw("'mg/dL' as unit"),
                DB::raw("COALESCE(validation_status, 'Valid') as status"),
                'measured_at as sort_date'
            );

        $physiological = DB::table('physiological_records')
            ->where('patient_id', $patientId)
            ->select(
                DB::raw("'Fisiologis' as type"),
                DB::raw("'Tekanan Darah' as title"),
                DB::raw("TO_CHAR(measured_at, 'DD Mon • HH24:MI') as time"),
                DB::raw("CONCAT(COALESCE(systolic::text, '-'), '/', COALESCE(diastolic::text, '-')) as value"),
                DB::raw("'mmHg' as unit"),
                DB::raw("COALESCE(validation_status, 'Valid') as status"),
                'measured_at as sort_date'
            );

        $activity = DB::table('activity_records as ar')
            ->leftJoin('activity_types as at', 'ar.activity_type_id', '=', 'at.activity_type_id')
            ->where('ar.patient_id', $patientId)
            ->select(
                DB::raw("'Aktivitas' as type"),
                DB::raw("COALESCE(at.activity_name, 'Aktivitas Fisik') as title"),
                DB::raw("TO_CHAR(ar.activity_date, 'DD Mon') as time"),
                DB::raw("ar.duration_minutes::text as value"),
                DB::raw("'menit' as unit"),
                DB::raw("COALESCE(ar.validation_status, 'Valid') as status"),
                'ar.activity_date as sort_date'
            );

        $meal = DB::table('meal_records as mr')
            ->leftJoin('meal_types as mt', 'mr.meal_type_id', '=', 'mt.meal_type_id')
            ->where('mr.patient_id', $patientId)
            ->select(
                DB::raw("'Makan' as type"),
                DB::raw("COALESCE(mt.meal_type_name, 'Pola Makan') as title"),
                DB::raw("TO_CHAR(mr.meal_date, 'DD Mon') as time"),
                DB::raw("COALESCE(mr.calories::text, '') as value"),
                DB::raw("'kkal' as unit"),
                DB::raw("COALESCE(mr.validation_status, 'Valid') as status"),
                'mr.meal_date as sort_date'
            );

        $medication = DB::table('medication_consumption_logs')
            ->where('patient_id', $patientId)
            ->select(
                DB::raw("'Obat' as type"),
                DB::raw("'Kepatuhan Obat' as title"),
                DB::raw("TO_CHAR(log_date, 'DD Mon') as time"),
                DB::raw("status as value"),
                DB::raw("'' as unit"),
                DB::raw("COALESCE(status, 'Valid') as status"),
                'log_date as sort_date'
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
