<?php

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function storeGlucose(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'measurement_type' => 'required|in:Puasa,Postprandial,Sewaktu,HbA1c',
            'glucose_value' => 'required|numeric',
            'measured_at' => 'required|date',
        ]);

        DB::table('glucose_records')->insert([
            'patient_id' => $patientId,
            'input_by_user_id' => $request->input_by_user_id,
            'measurement_type' => $request->measurement_type,
            'glucose_value' => $request->glucose_value,
            'validation_status' => 'Menunggu',
            'measured_at' => $request->measured_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Data glukosa berhasil dikirim dan menunggu validasi pasien'
        ], 201);
    }

    public function storePhysiological(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'systolic' => 'nullable|integer',
            'diastolic' => 'nullable|integer',
            'weight_kg' => 'nullable|numeric',
            'bmi' => 'nullable|numeric',
            'measured_at' => 'required|date',
        ]);

        DB::table('physiological_records')->insert([
            'patient_id' => $patientId,
            'input_by_user_id' => $request->input_by_user_id,
            'systolic' => $request->systolic,
            'diastolic' => $request->diastolic,
            'weight_kg' => $request->weight_kg,
            'bmi' => $request->bmi,
            'validation_status' => 'Menunggu',
            'measured_at' => $request->measured_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Data fisiologis berhasil dikirim dan menunggu validasi pasien'
        ], 201);
    }

    public function storeActivity(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'activity_type_id' => 'required|exists:activity_types,activity_type_id',
            'duration_minutes' => 'required|integer|min:1',
            'intensity' => 'required|in:Ringan,Sedang,Berat',
            'activity_date' => 'required|date',
        ]);

        DB::table('activity_records')->insert([
            'patient_id' => $patientId,
            'input_by_user_id' => $request->input_by_user_id,
            'activity_type_id' => $request->activity_type_id,
            'duration_minutes' => $request->duration_minutes,
            'intensity' => $request->intensity,
            'validation_status' => 'Menunggu',
            'activity_date' => $request->activity_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Data aktivitas berhasil dikirim dan menunggu validasi pasien'
        ], 201);
    }

    public function storeMeal(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'meal_type_id' => 'required|exists:meal_types,meal_type_id',
            'food_description' => 'nullable|string',
            'carbohydrate_estimate' => 'nullable|numeric',
            'calories' => 'nullable|numeric',
            'meal_date' => 'required|date',
        ]);

        DB::table('meal_records')->insert([
            'patient_id' => $patientId,
            'input_by_user_id' => $request->input_by_user_id,
            'meal_type_id' => $request->meal_type_id,
            'food_description' => $request->food_description,
            'carbohydrate_estimate' => $request->carbohydrate_estimate,
            'calories' => $request->calories,
            'validation_status' => 'Menunggu',
            'meal_date' => $request->meal_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Data makan berhasil dikirim dan menunggu validasi pasien'
        ], 201);
    }

    public function storeMedication(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'prescription_id' => 'nullable|exists:prescriptions,prescription_id',
            'schedule_id' => 'nullable|exists:prescription_schedules,schedule_id',
            'log_date' => 'required|date',
            'status' => 'required|in:Diminum,Tidak Diminum,Terlambat',
            'note' => 'nullable|string',
        ]);

        DB::table('medication_consumption_logs')->insert([
            'patient_id' => $patientId,
            'input_by_user_id' => $request->input_by_user_id,
            'prescription_id' => $request->prescription_id,
            'schedule_id' => $request->schedule_id,
            'log_date' => $request->log_date,
            'status' => $request->status,
            'note' => $request->note,
            'validation_status' => 'Menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Data kepatuhan obat berhasil dikirim dan menunggu validasi pasien'
        ], 201);
    }
}
