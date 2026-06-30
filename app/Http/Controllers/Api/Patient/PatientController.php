<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function homeSummary($patientId)
    {
        $latestRecommendation = DB::table('recommendations as r')
            ->join('clinical_notes as cn', 'r.clinical_note_id', '=', 'cn.clinical_note_id')
            ->join('doctor_patient_relations as dpr', 'cn.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('dpr.patient_id', $patientId)
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

        $pendingValidationCount =
            DB::table('glucose_records')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count()
            + DB::table('physiological_records')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count()
            + DB::table('activity_records')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count()
            + DB::table('meal_records')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count()
            + DB::table('medication_consumption_logs as l')
                ->join('prescription_schedules as ps', 'l.prescription_schedule_id', '=', 'ps.prescription_schedule_id')
                ->join('prescriptions as p', 'ps.prescription_id', '=', 'p.prescription_id')
                ->join('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
                ->where('dpr.patient_id', $patientId)
                ->where('l.validation_status', 'Menunggu')
                ->count();

        $today = now()->toDateString();

        $checklist = [
            'glucose' => DB::table('glucose_records')
                ->where('patient_id', $patientId)
                ->whereDate('measured_at', $today)
                ->exists(),

            'physiological' => DB::table('physiological_records')
                ->where('patient_id', $patientId)
                ->whereDate('measured_at', $today)
                ->exists(),

            'medication' => DB::table('medication_consumption_logs as l')
                ->join('prescription_schedules as ps', 'l.prescription_schedule_id', '=', 'ps.prescription_schedule_id')
                ->join('prescriptions as p', 'ps.prescription_id', '=', 'p.prescription_id')
                ->join('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
                ->where('dpr.patient_id', $patientId)
                ->whereDate('l.log_date', $today)
                ->exists(),

            'activity' => DB::table('activity_records')
                ->where('patient_id', $patientId)
                ->whereDate('activity_date', $today)
                ->exists(),

            'meal' => DB::table('meal_records')
                ->where('patient_id', $patientId)
                ->whereDate('meal_date', $today)
                ->exists(),
        ];

        $completedChecklist = collect($checklist)->filter()->count();

        return response()->json([
            'message' => 'Ringkasan home pasien berhasil diambil',
            'data' => [
                'latest_recommendation' => $latestRecommendation,
                'pending_validation_count' => $pendingValidationCount,
                'has_pending_validation' => $pendingValidationCount > 0,
                'daily_checklist' => [
                    'date' => $today,
                    'completed' => $completedChecklist,
                    'total' => 5,
                    'items' => $checklist,
                ],
            ],
        ]);
    }
}
