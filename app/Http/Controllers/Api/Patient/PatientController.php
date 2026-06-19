<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function homeSummary($patientId)
    {
        $latestRecommendation = DB::table('recommendations as r')
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

        $pendingValidationCount =
            DB::table('glucose_records')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count()
            +
            DB::table('physiological_records')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count()
            +
            DB::table('activity_records')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count()
            +
            DB::table('meal_records')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count()
            +
            DB::table('medication_consumption_logs')
                ->where('patient_id', $patientId)
                ->where('validation_status', 'Menunggu')
                ->count();

        $today = now()->toDateString();

        $checklist = [
            'glucose' => DB::table('glucose_records')
                ->where('patient_id', $patientId)
                ->whereDate('measured_at', $today)
                ->exists(),

            'medication' => DB::table('medication_consumption_logs')
                ->where('patient_id', $patientId)
                ->whereDate('log_date', $today)
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
                    'total' => 4,
                    'items' => $checklist,
                ],
            ],
        ]);
    }
}
