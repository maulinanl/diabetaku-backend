<?php

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HealthController extends Controller
{
    private function getNotificationTypeId($typeName)
    {
        return DB::table('notification_types')
            ->where('notification_type_name', $typeName)
            ->value('notification_type_id');
    }

    private function createNotification(
        $userId,
        $typeId,
        $title,
        $message,
        $referenceId = null,
        $referenceType = null
    ) {
        if (!$userId || !$typeId) return;

        $notificationId = DB::table('notifications')->insertGetId([
            'user_id' => $userId,
            'notification_type_id' => $typeId,
            'title' => $title,
            'message' => $message,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'notification_id');

        $sendPushNotification = function () use (
            $userId,
            $title,
            $message,
            $notificationId,
            $referenceId,
            $referenceType,
            $typeId
        ) {
            try {
                app(\App\Services\FcmService::class)->sendToUser(
                    $userId,
                    $title,
                    $message,
                    [
                        'notification_id' => $notificationId,
                        'reference_id' => $referenceId ?? '',
                        'reference_type' => $referenceType ?? '',
                        'notification_type_id' => $typeId,
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($sendPushNotification);
        } else {
            $sendPushNotification();
        }
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

    private function hasAcceptedRelation($patientId, $inputByUserId)
    {
        return DB::table('caregiver_patient_relations as fpr')
            ->join('caregivers as f', 'fpr.caregiver_id', '=', 'f.caregiver_id')
            ->where('f.user_id', $inputByUserId)
            ->where('fpr.patient_id', $patientId)
            ->where('fpr.status', 'Diterima')
            ->exists();
    }

    private function denyIfNoRelation($patientId, $inputByUserId)
    {
        if (!$this->hasAcceptedRelation($patientId, $inputByUserId)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke pasien ini'
            ], 403);
        }

        return null;
    }

    private function sendValidationNotificationToPatient(
        $patientId,
        $inputByUserId,
        $referenceId,
        $referenceType,
        $title,
        $dataName
    ) {
        $patientUserId = DB::table('patients')
            ->where('patient_id', $patientId)
            ->value('user_id');

        if (!$patientUserId) return;

        $inputByName = DB::table('users')
            ->where('user_id', $inputByUserId)
            ->value('full_name');

        $notificationTypeId = $this->getNotificationTypeId('Validasi Data');

        if (!$notificationTypeId) return;

        $this->createNotification(
            $patientUserId,
            $notificationTypeId,
            $title,
            ($inputByName ?? 'Keluarga') . ' menambahkan ' . $dataName . ' yang menunggu validasi Anda.',
            $referenceId,
            $referenceType
        );
    }

    public function storeGlucose(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'measurement_type' => ['required', Rule::in(['Puasa', 'Dua Jam Setelah Makan', 'Sewaktu'])],
            'glucose_value' => 'required|numeric',
            'measured_at' => 'required|date',
        ]);

        $denied = $this->denyIfNoRelation($patientId, $request->input_by_user_id);
        if ($denied) return $denied;

        return DB::transaction(function () use ($request, $patientId) {
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

            $this->sendValidationNotificationToPatient(
                $patientId,
                $request->input_by_user_id,
                $id,
                'validation_glucose',
                'Validasi Data Glukosa',
                'data glukosa'
            );

            return response()->json([
                'message' => 'Data glukosa berhasil dikirim dan menunggu validasi pasien',
                'glucose_id' => $id
            ], 201);
        });
    }

    public function storePhysiological(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'systolic' => 'nullable|integer',
            'diastolic' => 'nullable|integer',
            'weight_kg' => 'nullable|numeric',
            'measured_at' => 'required|date',
        ]);

        $denied = $this->denyIfNoRelation($patientId, $request->input_by_user_id);
        if ($denied) return $denied;

        return DB::transaction(function () use ($request, $patientId) {
            $id = DB::table('physiological_records')->insertGetId([
                'patient_id' => $patientId,
                'input_by_user_id' => $request->input_by_user_id,
                'systolic' => $request->systolic,
                'diastolic' => $request->diastolic,
                'weight_kg' => $request->weight_kg,
                'validation_status' => 'Menunggu',
                'measured_at' => $request->measured_at,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'physiological_id');

            $this->sendValidationNotificationToPatient(
                $patientId,
                $request->input_by_user_id,
                $id,
                'validation_physiological',
                'Validasi Data Fisiologis',
                'data fisiologis'
            );

            return response()->json([
                'message' => 'Data fisiologis berhasil dikirim dan menunggu validasi pasien',
                'physiological_id' => $id
            ], 201);
        });
    }

    public function storeActivity(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'activity_type_id' => 'required|exists:activity_types,activity_type_id',
            'duration_minutes' => 'required|integer|min:1',
            'intensity' => ['required', Rule::in(['Ringan', 'Sedang', 'Berat'])],
            'activity_date' => 'required|date',
        ]);

        $denied = $this->denyIfNoRelation($patientId, $request->input_by_user_id);
        if ($denied) return $denied;

        return DB::transaction(function () use ($request, $patientId) {
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

            $this->sendValidationNotificationToPatient(
                $patientId,
                $request->input_by_user_id,
                $id,
                'validation_activity',
                'Validasi Data Aktivitas',
                'data aktivitas'
            );

            return response()->json([
                'message' => 'Data aktivitas berhasil dikirim dan menunggu validasi pasien',
                'activity_id' => $id
            ], 201);
        });
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

        $denied = $this->denyIfNoRelation($patientId, $request->input_by_user_id);
        if ($denied) return $denied;

        return DB::transaction(function () use ($request, $patientId) {
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

            $this->sendValidationNotificationToPatient(
                $patientId,
                $request->input_by_user_id,
                $id,
                'validation_meal',
                'Validasi Data Makan',
                'data makan'
            );

            return response()->json([
                'message' => 'Data makan berhasil dikirim dan menunggu validasi pasien',
                'meal_id' => $id
            ], 201);
        });
    }

    public function storeMedication(Request $request, $patientId)
    {
        $request->validate([
            'input_by_user_id' => 'required|exists:users,user_id',
            'prescription_id' => 'required|exists:prescriptions,prescription_id',
            'prescription_schedule_id' => 'required|exists:prescription_schedules,prescription_schedule_id',
            'log_date' => 'required|date',
            'status' => ['required', Rule::in(['Diminum', 'Terlewat', 'Dibatalkan'])],
            'note' => 'nullable|string|max:500',
        ]);

        $denied = $this->denyIfNoRelation($patientId, $request->input_by_user_id);
        if ($denied) return $denied;

        $scheduleId = $request->prescription_schedule_id;

        $schedule = DB::table('prescription_schedules as ps')
            ->join('prescriptions as p', 'ps.prescription_id', '=', 'p.prescription_id')
            ->join('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->where('ps.prescription_schedule_id', $scheduleId)
            ->where('p.prescription_id', $request->prescription_id)
            ->where('dpr.patient_id', $patientId)
            ->select('ps.prescription_schedule_id')
            ->first();

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal resep tidak sesuai dengan pasien'
            ], 422);
        }

        $status = $request->status;

        return DB::transaction(function () use ($request, $patientId, $scheduleId, $status) {
            $existing = DB::table('medication_consumption_logs')
                ->where('prescription_schedule_id', $scheduleId)
                ->whereDate('log_date', $request->log_date)
                ->first();

            $payload = [
                'input_by_user_id' => $request->input_by_user_id,
                'status' => $status,
                'taken_at' => $status === 'Diminum' ? now() : null,
                'note' => $request->note,
                'validation_status' => 'Menunggu',
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('medication_consumption_logs')
                    ->where('log_id', $existing->log_id)
                    ->update($payload);

                $this->sendValidationNotificationToPatient(
                    $patientId,
                    $request->input_by_user_id,
                    $existing->log_id,
                    'validation_medication',
                    'Validasi Data Obat',
                    'data kepatuhan obat'
                );

                return response()->json([
                    'message' => 'Data kepatuhan obat berhasil diperbarui dan menunggu validasi pasien',
                    'data' => [
                        'log_id' => $existing->log_id,
                        'is_update' => true
                    ]
                ], 200);
            }

            $id = DB::table('medication_consumption_logs')->insertGetId([
                'prescription_schedule_id' => $scheduleId,
                'log_date' => $request->log_date,
                'created_at' => now(),
                ...$payload,
            ], 'log_id');

            $this->sendValidationNotificationToPatient(
                $patientId,
                $request->input_by_user_id,
                $id,
                'validation_medication',
                'Validasi Data Obat',
                'data kepatuhan obat'
            );

            return response()->json([
                'message' => 'Data kepatuhan obat berhasil dikirim dan menunggu validasi pasien',
                'data' => [
                    'log_id' => $id,
                    'is_update' => false
                ]
            ], 201);
        });
    }
}
