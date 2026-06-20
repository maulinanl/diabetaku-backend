<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function storeGlucose(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'measurement_type' => 'required|in:Puasa,Postprandial,Sewaktu,HbA1c',
            'glucose_value' => 'required|numeric',
            'measured_at' => 'required|date',
        ]);

        $id = DB::table('glucose_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'measurement_type' => $request->measurement_type,
            'glucose_value' => $request->glucose_value,
            'validation_status' => 'Valid',
            'measured_at' => $request->measured_at,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'glucose_id');

        return response()->json([
            'message' => 'Data gula darah berhasil ditambahkan',
            'glucose_id' => $id
        ], 201);
    }

    public function storePhysiological(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'systolic' => 'nullable|integer',
            'diastolic' => 'nullable|integer',
            'weight_kg' => 'nullable|numeric',
            'bmi' => 'nullable|numeric',
            'measured_at' => 'required|date',
        ]);

        $id = DB::table('physiological_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'systolic' => $request->systolic,
            'diastolic' => $request->diastolic,
            'weight_kg' => $request->weight_kg,
            'bmi' => $request->bmi,
            'validation_status' => 'Valid',
            'measured_at' => $request->measured_at,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'physiological_id');

        return response()->json([
            'message' => 'Data fisiologis berhasil ditambahkan',
            'physiological_id' => $id
        ], 201);
    }

    public function storeActivity(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'activity_type_id' => 'required|exists:activity_types,activity_type_id',
            'duration_minutes' => 'required|integer|min:1',
            'intensity' => 'required|in:Ringan,Sedang,Berat',
            'activity_date' => 'required|date',
        ]);

        $id = DB::table('activity_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'activity_type_id' => $request->activity_type_id,
            'duration_minutes' => $request->duration_minutes,
            'intensity' => $request->intensity,
            'validation_status' => 'Valid',
            'activity_date' => $request->activity_date,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'activity_id');

        return response()->json([
            'message' => 'Data aktivitas berhasil ditambahkan',
            'activity_id' => $id
        ], 201);
    }

    public function storeMeal(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'meal_type_id' => 'required|exists:meal_types,meal_type_id',
            'food_description' => 'nullable|string',
            'carbohydrate_estimate' => 'nullable|numeric',
            'calories' => 'nullable|numeric',
            'meal_date' => 'required|date',
        ]);

        $id = DB::table('meal_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'meal_type_id' => $request->meal_type_id,
            'food_description' => $request->food_description,
            'carbohydrate_estimate' => $request->carbohydrate_estimate,
            'calories' => $request->calories,
            'validation_status' => 'Valid',
            'meal_date' => $request->meal_date,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'meal_id');

        return response()->json([
            'message' => 'Data pola makan berhasil ditambahkan',
            'meal_id' => $id
        ], 201);
    }

    public function storeMedication(Request $request)
    {
        $request->validate([
            'prescription_id' => 'required|exists:prescriptions,prescription_id',
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'schedule_id' => 'required|exists:prescription_schedules,schedule_id',
            'log_date' => 'required|date',
            'status' => 'required|in:Diminum,Terlewat,Dibatalkan',
            'note' => 'nullable|string|max:500',
        ]);

        $prescription = DB::table('prescriptions')
            ->where('prescription_id', $request->prescription_id)
            ->where('patient_id', $request->patient_id)
            ->where('status', 'Aktif')
            ->first();

        if (!$prescription) {
            return response()->json([
                'message' => 'Resep tidak ditemukan atau tidak aktif'
            ], 404);
        }

        $schedule = DB::table('prescription_schedules')
            ->where('schedule_id', $request->schedule_id)
            ->where('prescription_id', $request->prescription_id)
            ->first();

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal tidak sesuai dengan resep'
            ], 422);
        }

        $exists = DB::table('medication_consumption_logs')
            ->where('patient_id', $request->patient_id)
            ->where('schedule_id', $request->schedule_id)
            ->whereDate('log_date', $request->log_date)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Obat pada jadwal ini sudah dicatat hari ini'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $id = DB::table('medication_consumption_logs')
                ->insertGetId([
                    'prescription_id' => $request->prescription_id,
                    'patient_id' => $request->patient_id,
                    'input_by_user_id' => $request->input_by_user_id,
                    'schedule_id' => $request->schedule_id,
                    'log_date' => $request->log_date,
                    'status' => $request->status,
                    'checked_at' => $request->status === 'Diminum'
                        ? now()
                        : null,
                    'cancelled_at' => $request->status === 'Dibatalkan'
                        ? now()
                        : null,
                    'note' => $request->note,
                    'validation_status' => 'Valid',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'log_id');

            DB::commit();

            return response()->json([
                'message' => 'Log konsumsi obat berhasil ditambahkan',
                'log_id' => $id
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan log konsumsi obat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history($patientId)
    {
        $roleCase = "
            CASE
                WHEN r.role_name ILIKE '%family%' OR r.role_name ILIKE '%keluarga%' THEN 'Keluarga'
                WHEN r.role_name ILIKE '%patient%' OR r.role_name ILIKE '%pasien%' THEN 'Pasien'
                ELSE 'Pasien'
            END as input_by_role
        ";

        return response()->json([
            'message' => 'Riwayat kesehatan berhasil diambil',
            'data' => [
                'glucose' => DB::table('glucose_records as gr')
                    ->leftJoin('users as iu', 'gr.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->where('gr.patient_id', $patientId)
                    ->select(
                        'gr.*',
                        DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                        DB::raw($roleCase)
                    )
                    ->orderByDesc('gr.measured_at')
                    ->get(),

                'physiological' => DB::table('physiological_records as pr')
                    ->leftJoin('users as iu', 'pr.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->where('pr.patient_id', $patientId)
                    ->select(
                        'pr.*',
                        DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                        DB::raw($roleCase)
                    )
                    ->orderByDesc('pr.measured_at')
                    ->get(),

                'activity' => DB::table('activity_records as ar')
                    ->leftJoin('users as iu', 'ar.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->where('ar.patient_id', $patientId)
                    ->select(
                        'ar.*',
                        DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                        DB::raw($roleCase)
                    )
                    ->orderByDesc('ar.activity_date')
                    ->get(),

                'meal' => DB::table('meal_records as mr')
                    ->leftJoin('users as iu', 'mr.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->where('mr.patient_id', $patientId)
                    ->select(
                        'mr.*',
                        DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                        DB::raw($roleCase)
                    )
                    ->orderByDesc('mr.meal_date')
                    ->get(),

                'medication' => DB::table('medication_consumption_logs as l')
                    ->join('prescriptions as p', 'l.prescription_id', '=', 'p.prescription_id')
                    ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
                    ->join('prescription_schedules as ps', 'l.schedule_id', '=', 'ps.schedule_id')
                    ->leftJoin('users as iu', 'l.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->where('l.patient_id', $patientId)
                    ->select(
                        'l.*',
                        'm.medication_name',
                        'ps.session',
                        'ps.dose_per_session',
                        DB::raw("COALESCE(iu.full_name, '-') as input_by_name"),
                        DB::raw($roleCase)
                    )
                    ->orderByDesc('l.log_date')
                    ->get(),
            ]
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
                'r.clinical_note_id',
                'cn.patient_id',
                'cn.doctor_id',
                'u.full_name as doctor_name',
                'r.category',
                'r.recommendation_text',
                'r.created_at'
            )
            ->orderByDesc('r.created_at')
            ->get();

        return response()->json([
            'message' => 'Riwayat rekomendasi berhasil diambil',
            'data' => $data
        ]);
    }

    public function activePrescriptions($patientId)
    {
        $data = DB::table('prescriptions as p')
            ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->join('prescription_schedules as ps', 'p.prescription_id', '=', 'ps.prescription_id')
            ->where('p.patient_id', $patientId)
            ->where('p.status', 'Aktif')
            ->whereDate('p.valid_from', '<=', now())
            ->whereDate('p.valid_until', '>=', now())
            ->select(
                'p.prescription_id',
                'ps.schedule_id',
                'm.medication_name',
                'm.description',
                'p.dosage',
                'p.form',
                'p.meal_rule',
                'p.notes',
                'ps.session',
                'ps.dose_per_session'
            )
            ->orderBy('ps.session')
            ->get();

        return response()->json([
            'message' => 'Resep aktif berhasil diambil',
            'data' => $data
        ]);
    }

    public function latestRecommendation($patientId)
    {
        $data = DB::table('recommendations as r')
            ->join('doctors as d', 'r.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('r.patient_id', $patientId)
            ->select(
                'r.recommendation_id',
                'u.full_name as doctor_name',
                'r.category',
                'r.content',
                'r.created_at'
            )
            ->orderByDesc('r.created_at')
            ->first();

        return response()->json([
            'message' => 'Rekomendasi terbaru berhasil diambil',
            'data' => $data
        ]);
    }

    public function pendingValidations($patientId)
    {
        $glucose = DB::table('glucose_records as gr')
            ->join('users as u', 'gr.input_by_user_id', '=', 'u.user_id')
            ->where('gr.patient_id', $patientId)
            ->where('gr.validation_status', 'Menunggu')
            ->select(
                DB::raw("'glucose' as record_type"),
                'gr.glucose_id as record_id',
                DB::raw("CONCAT('Glukosa ', gr.measurement_type) as title"),
                'gr.measured_at as date',
                DB::raw("CAST(gr.glucose_value as TEXT) as value"),
                DB::raw("'mg/dL' as unit"),
                'u.full_name as input_by',
                DB::raw("'Keluarga' as relation")
            );

        $physiological = DB::table('physiological_records as pr')
            ->join('users as u', 'pr.input_by_user_id', '=', 'u.user_id')
            ->where('pr.patient_id', $patientId)
            ->where('pr.validation_status', 'Menunggu')
            ->select(
                DB::raw("'physiological' as record_type"),
                'pr.physiological_id as record_id',
                DB::raw("'Tekanan Darah' as title"),
                'pr.measured_at as date',
                DB::raw("CONCAT(COALESCE(pr.systolic::TEXT, '-'), '/', COALESCE(pr.diastolic::TEXT, '-')) as value"),
                DB::raw("'mmHg' as unit"),
                'u.full_name as input_by',
                DB::raw("'Keluarga' as relation")
            );

        $activity = DB::table('activity_records as ar')
            ->join('users as u', 'ar.input_by_user_id', '=', 'u.user_id')
            ->where('ar.patient_id', $patientId)
            ->where('ar.validation_status', 'Menunggu')
            ->select(
                DB::raw("'activity' as record_type"),
                'ar.activity_id as record_id',
                DB::raw("'Aktivitas Fisik' as title"),
                'ar.activity_date as date',
                DB::raw("CAST(ar.duration_minutes as TEXT) as value"),
                DB::raw("'menit' as unit"),
                'u.full_name as input_by',
                DB::raw("'Keluarga' as relation")
            );

        $meal = DB::table('meal_records as mr')
            ->join('users as u', 'mr.input_by_user_id', '=', 'u.user_id')
            ->where('mr.patient_id', $patientId)
            ->where('mr.validation_status', 'Menunggu')
            ->select(
                DB::raw("'meal' as record_type"),
                'mr.meal_id as record_id',
                DB::raw("'Pola Makan' as title"),
                'mr.meal_date as date',
                DB::raw("COALESCE(CAST(mr.carbohydrate_estimate as TEXT), '-') as value"),
                DB::raw("'gram' as unit"),
                'u.full_name as input_by',
                DB::raw("'Keluarga' as relation")
            );

        $data = $glucose
            ->unionAll($physiological)
            ->unionAll($activity)
            ->unionAll($meal)
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'message' => 'Data menunggu validasi berhasil diambil',
            'data' => $data
        ]);
    }

    public function respondValidation(Request $request)
    {
        $request->validate([
            'record_type' => 'required|in:glucose,physiological,activity,meal',
            'record_id' => 'required|integer',
            'status' => 'required|in:Valid,Ditolak',
        ]);

        $tableMap = [
            'glucose' => ['table' => 'glucose_records', 'id' => 'glucose_id'],
            'physiological' => ['table' => 'physiological_records', 'id' => 'physiological_id'],
            'activity' => ['table' => 'activity_records', 'id' => 'activity_id'],
            'meal' => ['table' => 'meal_records', 'id' => 'meal_id'],
        ];

        $target = $tableMap[$request->record_type];

        $updated = DB::table($target['table'])
            ->where($target['id'], $request->record_id)
            ->where('validation_status', 'Menunggu')
            ->update([
                'validation_status' => $request->status,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'message' => 'Data tidak ditemukan atau sudah divalidasi'
            ], 404);
        }

        return response()->json([
            'message' => $request->status === 'Valid'
                ? 'Data berhasil diterima'
                : 'Data berhasil ditolak'
        ]);
    }

}
