<?php

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    private function createNotification(
        $userId,
        $typeId,
        $title,
        $message,
        $referenceId = null,
        $referenceType = null
    ) {
        if (!$userId) return;

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

    private function patientUserId($patientId)
    {
        return DB::table('patients')
            ->where('patient_id', $patientId)
            ->value('user_id');
    }

    private function inputterName($userId)
    {
        return DB::table('users')
            ->where('user_id', $userId)
            ->value('full_name') ?? 'Keluarga';
    }

    public function storeGlucose(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'measurement_type' => 'required|in:Puasa,Postprandial,Sewaktu,HbA1c',
            'glucose_value' => 'required|numeric',
            'measured_at' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $patientId) {
            $id = DB::table('glucose_records')->insertGetId([
                'patient_id' => $patientId,
                'input_by_user_id' => $request->input_by_user_id,
                'measurement_type' => $request->measurement_type,
                'glucose_value' => $request->glucose_value,
                'validation_status' => 'Menunggu',
                'measured_at' => $request->measured_at,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'glucose_id');

            $this->createNotification(
                $this->patientUserId($patientId),
                5,
                'Validasi Data Glukosa',
                $this->inputterName($request->input_by_user_id) . ' menambahkan data glukosa yang menunggu validasi Anda.',
                $id,
                'validation_glucose'
            );
        });

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

        DB::transaction(function () use ($request, $patientId) {
            $id = DB::table('physiological_records')->insertGetId([
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
            ], 'physiological_id');

            $this->createNotification(
                $this->patientUserId($patientId),
                5,
                'Validasi Data Fisiologis',
                $this->inputterName($request->input_by_user_id) . ' menambahkan data fisiologis yang menunggu validasi Anda.',
                $id,
                'validation_physiological'
            );
        });

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

        DB::transaction(function () use ($request, $patientId) {
            $id = DB::table('activity_records')->insertGetId([
                'patient_id' => $patientId,
                'input_by_user_id' => $request->input_by_user_id,
                'activity_type_id' => $request->activity_type_id,
                'duration_minutes' => $request->duration_minutes,
                'intensity' => $request->intensity,
                'validation_status' => 'Menunggu',
                'activity_date' => $request->activity_date,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'activity_id');

            $this->createNotification(
                $this->patientUserId($patientId),
                5,
                'Validasi Data Aktivitas',
                $this->inputterName($request->input_by_user_id) . ' menambahkan data aktivitas yang menunggu validasi Anda.',
                $id,
                'validation_activity'
            );
        });

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

        DB::transaction(function () use ($request, $patientId) {
            $id = DB::table('meal_records')->insertGetId([
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
            ], 'meal_id');

            $this->createNotification(
                $this->patientUserId($patientId),
                5,
                'Validasi Data Makan',
                $this->inputterName($request->input_by_user_id) . ' menambahkan data makan yang menunggu validasi Anda.',
                $id,
                'validation_meal'
            );
        });

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

        DB::transaction(function () use ($request, $patientId) {
            $id = DB::table('medication_consumption_logs')->insertGetId([
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
            ], 'log_id');

            $this->createNotification(
                $this->patientUserId($patientId),
                5,
                'Validasi Data Obat',
                $this->inputterName($request->input_by_user_id) . ' menambahkan data kepatuhan obat yang menunggu validasi Anda.',
                $id,
                'validation_medication'
            );
        });

        return response()->json([
            'message' => 'Data kepatuhan obat berhasil dikirim dan menunggu validasi pasien'
        ], 201);
    }
}
