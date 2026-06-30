<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show($patientId)
    {
        $profile = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('blood_types as bt', 'p.blood_type_id', '=', 'bt.blood_type_id')
            ->leftJoin('rhesus_types as rt', 'p.rhesus_type_id', '=', 'rt.rhesus_type_id')
            ->where('p.patient_id', $patientId)
            ->select(
                'p.patient_id',
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'p.date_of_birth',
                'u.gender',
                'p.diabetes_type',
                'p.diagnosis_date',
                'p.height_cm',
                'p.blood_type_id',
                'bt.blood_type',
                'p.rhesus_type_id',
                'rt.rhesus_type'
            )
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profil pasien tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Profil pasien berhasil diambil',
            'data' => $profile
        ]);
    }

    public function update(Request $request, $patientId)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'diabetes_type' => 'required|in:Tipe 1,Tipe 2',
            'diagnosis_date' => 'nullable|date',
            'height_cm' => 'nullable|numeric',
            'blood_type_id' => 'nullable|exists:blood_types,blood_type_id',
            'rhesus_type_id' => 'nullable|exists:rhesus_types,rhesus_type_id',
        ]);

        $patient = DB::table('patients')
            ->where('patient_id', $patientId)
            ->first();

        if (!$patient) {
            return response()->json([
                'message' => 'Profil pasien tidak ditemukan'
            ], 404);
        }

        DB::transaction(function () use ($request, $patient, $patientId) {
            DB::table('users')
                ->where('user_id', $patient->user_id)
                ->update([
                    'full_name' => $request->full_name,
                    'phone_number' => $request->phone_number,
                    'gender' => $request->gender,
                    'updated_at' => now(),
                ]);

            DB::table('patients')
                ->where('patient_id', $patientId)
                ->update([
                    'date_of_birth' => $request->date_of_birth,
                    'diabetes_type' => $request->diabetes_type,
                    'diagnosis_date' => $request->diagnosis_date,
                    'height_cm' => $request->height_cm,
                    'blood_type_id' => $request->blood_type_id,
                    'rhesus_type_id' => $request->rhesus_type_id,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'message' => 'Profil pasien berhasil diperbarui'
        ]);
    }

    public function dashboard($patientId)
    {
        $patient = DB::table('patients')
            ->where('patient_id', $patientId)
            ->first();

        if (!$patient) {
            return response()->json([
                'message' => 'Pasien tidak ditemukan'
            ], 404);
        }

        $glucose = DB::table('glucose_records')
            ->where('patient_id', $patientId)
            ->latest('measured_at')
            ->first();

        $physio = DB::table('physiological_records')
            ->where('patient_id', $patientId)
            ->latest('measured_at')
            ->first();

        return response()->json([
            'data' => [
                'latest_glucose' => $glucose,
                'latest_physiological' => $physio,
                'glucose' => [
                    'value' => $glucose?->glucose_value,
                    'status' => $glucose
                        ? ((float) $glucose->glucose_value > 180 ? 'Tinggi' : 'Normal')
                        : '-',
                ],
                'blood_pressure' => [
                    'value' => $physio
                        ? "{$physio->systolic}/{$physio->diastolic}"
                        : '-',
                    'status' => 'Tercatat',
                ],
                'weight' => [
                    'value' => $physio?->weight_kg,
                    'status' => $physio ? 'Tercatat' : '-',
                ],
            ]
        ]);
    }

    public function latestRecommendation($patientId)
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
                'cn.clinical_note_id',
                'u.full_name as doctor_name',
                'r.created_at'
            )
            ->orderByDesc('r.created_at')
            ->first();

        return response()->json([
            'data' => $data
        ]);
    }

    public function homeSummary($patientId)
    {
        $today = now()->toDateString();

        $latestRecommendation = DB::table('recommendations as r')
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
                'u.full_name as doctor_name'
            )
            ->orderByDesc('r.created_at')
            ->first();

        $glucoseDone = DB::table('glucose_records')
            ->where('patient_id', $patientId)
            ->whereDate('measured_at', $today)
            ->exists();

        $physiologicalDone = DB::table('physiological_records')
            ->where('patient_id', $patientId)
            ->whereDate('measured_at', $today)
            ->exists();

        $activityDone = DB::table('activity_records')
            ->where('patient_id', $patientId)
            ->whereDate('activity_date', $today)
            ->exists();

        $mealDone = DB::table('meal_records')
            ->where('patient_id', $patientId)
            ->whereDate('meal_date', $today)
            ->exists();

        $medicationDone = DB::table('medication_consumption_logs as l')
            ->join('prescription_schedules as ps', 'l.prescription_schedule_id', '=', 'ps.prescription_schedule_id')
            ->join('prescriptions as p', 'ps.prescription_id', '=', 'p.prescription_id')
            ->join('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->where('dpr.patient_id', $patientId)
            ->whereDate('l.log_date', $today)
            ->exists();

        $items = [
            'glucose' => $glucoseDone,
            'physiological' => $physiologicalDone,
            'medication' => $medicationDone,
            'activity' => $activityDone,
            'meal' => $mealDone,
        ];

        $completed = collect($items)->filter()->count();

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

        return response()->json([
            'message' => 'Ringkasan home pasien berhasil diambil',
            'data' => [
                'latest_recommendation' => $latestRecommendation,
                'has_pending_validation' => $pendingValidationCount > 0,
                'pending_validation_count' => $pendingValidationCount,
                'daily_checklist' => [
                    'completed' => $completed,
                    'total' => 5,
                    'items' => $items,
                ],
            ],
        ]);
    }
}
