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
            'food_description' => 'required|string',
            'carbohydrate_estimate' => 'nullable|numeric',
            'meal_date' => 'required|date',
        ]);

        $id = DB::table('meal_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'meal_type_id' => $request->meal_type_id,
            'food_description' => $request->food_description,
            'carbohydrate_estimate' => $request->carbohydrate_estimate,
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
            'note' => 'nullable|string',
        ]);

        $id = DB::table('medication_consumption_logs')->insertGetId([
            'prescription_id' => $request->prescription_id,
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'schedule_id' => $request->schedule_id,
            'log_date' => $request->log_date,
            'status' => $request->status,
            'checked_at' => $request->status === 'Diminum' ? now() : null,
            'cancelled_at' => $request->status === 'Dibatalkan' ? now() : null,
            'note' => $request->note,
            'validation_status' => 'Valid',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'log_id');

        return response()->json([
            'message' => 'Log konsumsi obat berhasil ditambahkan',
            'log_id' => $id
        ], 201);
    }

    public function history($patientId)
    {
        return response()->json([
            'message' => 'Riwayat kesehatan berhasil diambil',
            'data' => [
                'glucose' => DB::table('glucose_records')->where('patient_id', $patientId)->orderByDesc('measured_at')->get(),
                'physiological' => DB::table('physiological_records')->where('patient_id', $patientId)->orderByDesc('measured_at')->get(),
                'activity' => DB::table('activity_records')->where('patient_id', $patientId)->orderByDesc('activity_date')->get(),
                'meal' => DB::table('meal_records')->where('patient_id', $patientId)->orderByDesc('meal_date')->get(),
                'medication' => DB::table('medication_consumption_logs')->where('patient_id', $patientId)->orderByDesc('log_date')->get(),
            ]
        ]);
    }
}
