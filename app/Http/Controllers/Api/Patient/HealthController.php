<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
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

    private function notifyFamilyValidationResult($record, $recordType, $status)
    {
        $inputByUserId = $record->input_by_user_id ?? null;
        if (!$inputByUserId) return;

        $patientName = $this->getPatientName($record->patient_id);

        $label = match ($recordType) {
            'glucose' => 'data glukosa',
            'physiological' => 'data fisiologis',
            'activity' => 'data aktivitas',
            'meal' => 'data makan',
            'medication' => 'data obat',
            default => 'data kesehatan',
        };

        $this->createNotification(
            $inputByUserId,
            5,
            $status === 'Valid' ? 'Data Diterima' : 'Data Ditolak',
            $status === 'Valid'
                ? "{$patientName} menerima {$label} yang Anda tambahkan."
                : "{$patientName} menolak {$label} yang Anda tambahkan.",
            $record->patient_id,
            'validation_result'
        );
    }

    private function notifyDoctorsIfAbnormal($patientId, $parameterName, $value, $title, $message)
    {
        $doctors = DB::table('doctor_patient_relations as dpr')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->where('dpr.patient_id', $patientId)
            ->where('dpr.status', 'Diterima')
            ->select('d.doctor_id', 'd.user_id')
            ->get();

        foreach ($doctors as $doctor) {
            $threshold = $this->getThreshold($patientId, $doctor->doctor_id, $parameterName);

            if ($this->isOutOfRange($value, $threshold)) {
                $this->createNotification(
                    $doctor->user_id,
                    1,
                    $title,
                    $message,
                    $patientId,
                    'abnormal'
                );
            }
        }
    }

    private function getThreshold($patientId, $doctorId, $parameterName)
    {
        return DB::table('clinical_parameters as cp')
            ->leftJoin('patient_custom_thresholds as pct', function ($join) use ($patientId, $doctorId) {
                $join->on('cp.parameter_id', '=', 'pct.parameter_id')
                    ->where('pct.patient_id', '=', $patientId)
                    ->where('pct.set_by_doctor_id', '=', $doctorId);
            })
            ->whereRaw('LOWER(cp.parameter_name) = LOWER(?)', [$parameterName])
            ->select(
                'cp.parameter_name',
                'cp.unit',
                DB::raw('COALESCE(pct.custom_min, cp.default_min) as min_value'),
                DB::raw('COALESCE(pct.custom_max, cp.default_max) as max_value')
            )
            ->first();
    }

    private function isOutOfRange($value, $threshold)
    {
        if (!$threshold || $value === null) return false;

        return (float) $value < (float) $threshold->min_value ||
            (float) $value > (float) $threshold->max_value;
    }

    private function checkGlucoseAbnormal($patientId, $measurementType, $glucoseValue)
    {
        $parameterName = match ($measurementType) {
            'Puasa' => 'Gula Darah Puasa',
            'Dua Jam Setelah Makan' => 'Gula Darah Postprandial',
            'Sewaktu' => 'Gula Darah Sewaktu',
            default => null,
        };

        if (!$parameterName) return;

        $patientName = $this->getPatientName($patientId);

        $this->notifyDoctorsIfAbnormal(
            $patientId,
            $parameterName,
            $glucoseValue,
            'Data Glukosa Abnormal',
            "{$patientName} memiliki {$parameterName} sebesar {$glucoseValue} mg/dL, berada di luar batas normal."
        );
    }

    private function checkPhysiologicalAbnormal($patientId, $systolic = null, $diastolic = null, $bmi = null)
    {
        $patientName = $this->getPatientName($patientId);

        if ($systolic !== null) {
            $this->notifyDoctorsIfAbnormal(
                $patientId,
                'Sistolik',
                $systolic,
                'Tekanan Darah Abnormal',
                "{$patientName} memiliki tekanan darah sistolik sebesar {$systolic} mmHg, berada di luar batas normal."
            );
        }

        if ($diastolic !== null) {
            $this->notifyDoctorsIfAbnormal(
                $patientId,
                'Diastolik',
                $diastolic,
                'Tekanan Darah Abnormal',
                "{$patientName} memiliki tekanan darah diastolik sebesar {$diastolic} mmHg, berada di luar batas normal."
            );
        }

        if ($bmi !== null) {
            $this->notifyDoctorsIfAbnormal(
                $patientId,
                'BMI',
                $bmi,
                'BMI Abnormal',
                "{$patientName} memiliki BMI sebesar {$bmi} kg/m2, berada di luar batas normal."
            );
        }
    }

    public function storeGlucose(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'measurement_type' => 'required|in:Puasa,Dua Jam Setelah Makan,Sewaktu',
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

        $this->checkGlucoseAbnormal($request->patient_id, $request->measurement_type, $request->glucose_value);

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

        $this->checkPhysiologicalAbnormal(
            $request->patient_id,
            $request->systolic,
            $request->diastolic,
            $request->bmi
        );

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
            'status' => 'required|in:Diminum,Tidak Diminum,Terlambat',
            'note' => 'nullable|string|max:500',
        ]);

        $existing = DB::table('medication_consumption_logs')
            ->where('prescription_id', $request->prescription_id)
            ->where('patient_id', $request->patient_id)
            ->where('schedule_id', $request->schedule_id)
            ->whereDate('log_date', $request->log_date)
            ->first();

        $payload = [
            'input_by_user_id' => $request->input_by_user_id,
            'status' => $request->status,
            'checked_at' => $request->status === 'Diminum' ? now() : null,
            'cancelled_at' => $request->status === 'Tidak Diminum' ? now() : null,
            'note' => $request->note,
            'validation_status' => 'Valid',
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('medication_consumption_logs')
                ->where('log_id', $existing->log_id)
                ->update($payload);

            return response()->json([
                'message' => 'Log konsumsi obat berhasil diperbarui',
                'data' => [
                    'log_id' => $existing->log_id,
                    'is_update' => true,
                ]
            ], 200);
        }

        $logId = DB::table('medication_consumption_logs')->insertGetId(array_merge($payload, [
            'prescription_id' => $request->prescription_id,
            'patient_id' => $request->patient_id,
            'schedule_id' => $request->schedule_id,
            'log_date' => $request->log_date,
            'created_at' => now(),
        ]), 'log_id');

        return response()->json([
            'message' => 'Log konsumsi obat berhasil disimpan',
            'data' => [
                'log_id' => $logId,
                'is_update' => false,
            ]
        ], 201);
    }

    public function activePrescriptions($patientId)
    {
        $today = now()->toDateString();

        $data = DB::table('prescriptions as p')
            ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->join('prescription_schedules as ps', 'p.prescription_id', '=', 'ps.prescription_id')
            ->join('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
            ->leftJoin('medication_consumption_logs as l', function ($join) use ($patientId, $today) {
                $join->on('l.prescription_id', '=', 'p.prescription_id')
                    ->on('l.schedule_id', '=', 'ps.schedule_id')
                    ->where('l.patient_id', '=', $patientId)
                    ->whereDate('l.log_date', '=', $today);
            })
            ->where('p.patient_id', $patientId)
            ->where('p.status', 'Aktif')
            ->where('ps.is_active', true)
            ->whereDate('p.valid_from', '<=', $today)
            ->whereDate('p.valid_until', '>=', $today)
            ->select(
                'p.prescription_id',
                'ps.schedule_id',
                'm.medication_name',
                'm.description',
                'p.dosage',
                'p.form',
                'p.meal_rule',
                'p.notes',
                'ms.session_id',
                'ms.session_name',
                'ms.start_time',
                'ms.end_time',
                'ms.default_reminder_time',
                'ps.dose_per_session',
                'ps.reminder_time',
                'l.log_id',
                'l.status as log_status',
                'l.checked_at',
                'l.cancelled_at',
                'l.note as log_note',
                DB::raw("CASE WHEN l.status = 'Diminum' THEN true ELSE false END as checked"),
                DB::raw("CASE WHEN l.log_id IS NULL THEN false ELSE true END as already_logged")
            )
            ->orderByRaw('COALESCE(ps.reminder_time, ms.default_reminder_time)')
            ->get();

        return response()->json([
            'message' => 'Resep aktif berhasil diambil',
            'data' => $data
        ]);
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
                    ->select('gr.*', DB::raw("COALESCE(iu.full_name, '-') as input_by_name"), DB::raw($roleCase))
                    ->orderByDesc('gr.measured_at')
                    ->get(),

                'physiological' => DB::table('physiological_records as pr')
                    ->leftJoin('users as iu', 'pr.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->where('pr.patient_id', $patientId)
                    ->select('pr.*', DB::raw("COALESCE(iu.full_name, '-') as input_by_name"), DB::raw($roleCase))
                    ->orderByDesc('pr.measured_at')
                    ->get(),

                'activity' => DB::table('activity_records as ar')
                    ->leftJoin('users as iu', 'ar.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->leftJoin('activity_types as at', 'ar.activity_type_id', '=', 'at.activity_type_id')
                    ->where('ar.patient_id', $patientId)
                    ->select('ar.*', 'at.activity_name', DB::raw("COALESCE(iu.full_name, '-') as input_by_name"), DB::raw($roleCase))
                    ->orderByDesc('ar.activity_date')
                    ->get(),

                'meal' => DB::table('meal_records as mr')
                    ->leftJoin('users as iu', 'mr.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->leftJoin('meal_types as mt', 'mr.meal_type_id', '=', 'mt.meal_type_id')
                    ->where('mr.patient_id', $patientId)
                    ->select('mr.*', 'mt.meal_type_name', DB::raw("COALESCE(iu.full_name, '-') as input_by_name"), DB::raw($roleCase))
                    ->orderByDesc('mr.meal_date')
                    ->get(),

                'medication' => DB::table('medication_consumption_logs as l')
                    ->leftJoin('prescriptions as p', 'l.prescription_id', '=', 'p.prescription_id')
                    ->leftJoin('medications as m', 'p.medication_id', '=', 'm.medication_id')
                    ->leftJoin('prescription_schedules as ps', 'l.schedule_id', '=', 'ps.schedule_id')
                    ->leftJoin('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
                    ->leftJoin('users as iu', 'l.input_by_user_id', '=', 'iu.user_id')
                    ->leftJoin('roles as r', 'iu.role_id', '=', 'r.role_id')
                    ->where('l.patient_id', $patientId)
                    ->select(
                        'l.*',
                        'm.medication_name',
                        'ms.session_name',
                        'ms.start_time',
                        'ms.end_time',
                        'ps.dose_per_session',
                        DB::raw("COALESCE(ps.reminder_time, ms.default_reminder_time) as reminder_time"),
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

    public function latestRecommendation($patientId)
    {
        $data = DB::table('recommendations as r')
            ->join('clinical_notes as cn', 'r.clinical_note_id', '=', 'cn.clinical_note_id')
            ->join('doctors as d', 'cn.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('cn.patient_id', $patientId)
            ->select(
                'r.recommendation_id',
                'r.clinical_note_id',
                'u.full_name as doctor_name',
                'r.category',
                'r.recommendation_text',
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

        $medication = DB::table('medication_consumption_logs as l')
            ->join('users as u', 'l.input_by_user_id', '=', 'u.user_id')
            ->where('l.patient_id', $patientId)
            ->where('l.validation_status', 'Menunggu')
            ->select(
                DB::raw("'medication' as record_type"),
                'l.log_id as record_id',
                DB::raw("'Kepatuhan Obat' as title"),
                'l.log_date as date',
                DB::raw("l.status::TEXT as value"),
                DB::raw("''::TEXT as unit"),
                'u.full_name as input_by',
                DB::raw("'Keluarga' as relation")
            );

        $data = $glucose
            ->unionAll($physiological)
            ->unionAll($activity)
            ->unionAll($meal)
            ->unionAll($medication)
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
            'record_type' => 'required|in:glucose,physiological,activity,meal,medication',
            'record_id' => 'required|integer',
            'status' => 'required|in:Valid,Ditolak',
        ]);

        $tableMap = [
            'glucose' => ['table' => 'glucose_records', 'id' => 'glucose_id'],
            'physiological' => ['table' => 'physiological_records', 'id' => 'physiological_id'],
            'activity' => ['table' => 'activity_records', 'id' => 'activity_id'],
            'meal' => ['table' => 'meal_records', 'id' => 'meal_id'],
            'medication' => ['table' => 'medication_consumption_logs', 'id' => 'log_id'],
        ];

        $target = $tableMap[$request->record_type];

        $record = DB::table($target['table'])
            ->where($target['id'], $request->record_id)
            ->first();

        if (!$record || $record->validation_status !== 'Menunggu') {
            return response()->json([
                'message' => 'Data tidak ditemukan atau sudah divalidasi'
            ], 404);
        }

        DB::table($target['table'])
            ->where($target['id'], $request->record_id)
            ->update([
                'validation_status' => $request->status,
                'validated_at' => now(),
                'updated_at' => now(),
            ]);

        $this->notifyFamilyValidationResult($record, $request->record_type, $request->status);

        if ($request->status === 'Valid') {
            if ($request->record_type === 'glucose') {
                $this->checkGlucoseAbnormal($record->patient_id, $record->measurement_type, $record->glucose_value);
            }

            if ($request->record_type === 'physiological') {
                $this->checkPhysiologicalAbnormal($record->patient_id, $record->systolic, $record->diastolic, $record->bmi);
            }
        }

        return response()->json([
            'message' => $request->status === 'Valid'
                ? 'Data berhasil diterima'
                : 'Data berhasil ditolak'
        ]);
    }
}
