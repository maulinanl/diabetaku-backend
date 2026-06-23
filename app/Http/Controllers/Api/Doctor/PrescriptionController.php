<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrescriptionController extends Controller
{
    private array $mealRules = [
        'Sebelum Makan',
        'Sesudah Makan',
        'Bersama Makan',
        'Bebas',
    ];

    private array $dosageForms = [
        'Tablet',
        'Kapsul',
        'Sirup',
        'Injeksi',
        'Tetes',
        'Krim/Salep',
    ];

    private function getNotificationTypeId($typeName)
    {
        return DB::table('notification_types')
            ->where('notification_type_name', $typeName)
            ->value('notification_type_id');
    }

    private function createNotification($userId, $typeName, $title, $message, $referenceId = null, $referenceType = null)
    {
        if (!$userId) return;

        $typeId = $this->getNotificationTypeId($typeName);
        if (!$typeId) return;

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

    private function notifyPrescriptionChanged(
    $patientId,
    $prescriptionId,
    $title,
    $patientMessage,
    $familyMessage = null
)
{
    $patientUserId = DB::table('patients')
        ->where('patient_id', $patientId)
        ->value('user_id');

    $this->createNotification(
        $patientUserId,
        'Pengingat Obat',
        $title,
        $patientMessage,
        $prescriptionId,
        'prescription'
    );

    $familyUserIds = DB::table('family_patient_relations as fpr')
        ->join('families as f', 'fpr.family_id', '=', 'f.family_id')
        ->where('fpr.patient_id', $patientId)
        ->where('fpr.status', 'Diterima')
        ->pluck('f.user_id');

    foreach ($familyUserIds as $userId) {
        $this->createNotification(
            $userId,
            'Pengingat Obat',
            $title,
            $familyMessage ?? $patientMessage,
            $prescriptionId,
            'prescription'
        );
    }
}

    public function searchMedications(Request $request)
    {
        $keyword = trim($request->query('keyword', ''));

        $data = DB::table('medications')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where('medication_name', 'ILIKE', "%{$keyword}%");
            })
            ->select('medication_id', 'medication_name', 'description')
            ->orderBy('medication_name')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Data obat berhasil diambil',
            'data' => $data,
        ]);
    }

    public function sessions()
    {
        $data = DB::table('medication_sessions')
            ->where('is_active', true)
            ->select(
                'session_id',
                'session_name',
                'start_time',
                'end_time',
                'default_reminder_time'
            )
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'message' => 'Sesi minum obat berhasil diambil',
            'data' => $data,
        ]);
    }

    public function active(Request $request, $patientId)
    {
        $doctorId = $request->query('doctor_id');

        $data = DB::table('prescriptions as p')
            ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->join('doctors as d', 'p.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('p.patient_id', $patientId)
            ->where('p.status', 'Aktif')
            ->select(
                'p.prescription_id',
                'p.patient_id',
                'p.doctor_id',
                'u.full_name as doctor_name',
                'm.medication_id',
                'm.medication_name',
                'm.description',
                'p.dosage',
                'p.form',
                'p.indication',
                'p.meal_rule',
                'p.notes',
                'p.status',
                'p.valid_from',
                'p.valid_until',
                'p.replaced_by',
                'p.created_at',
                'p.updated_at'
            )
            ->orderByDesc('p.created_at')
            ->get();

        foreach ($data as $item) {
            $item->is_mine = $doctorId ? ((int) $item->doctor_id === (int) $doctorId) : false;

            $item->schedules = DB::table('prescription_schedules as ps')
                ->join('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
                ->where('ps.prescription_id', $item->prescription_id)
                ->where('ps.is_active', true)
                ->select(
                    'ps.schedule_id',
                    'ps.session_id',
                    'ms.session_name',
                    'ms.start_time',
                    'ms.end_time',
                    'ms.default_reminder_time',
                    'ps.dose_per_session',
                    'ps.reminder_time',
                    'ps.is_active'
                )
                ->orderBy('ms.start_time')
                ->get();
        }

        return response()->json([
            'message' => 'Resep aktif berhasil diambil',
            'data' => $data,
        ]);
    }

    public function history(Request $request, $patientId)
    {
        $doctorId = $request->query('doctor_id');

        $data = DB::table('prescriptions as p')
            ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->join('doctors as d', 'p.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('p.patient_id', $patientId)
            ->whereIn('p.status', ['Selesai', 'Diganti'])
            ->select(
                'p.prescription_id',
                'p.patient_id',
                'p.doctor_id',
                'u.full_name as doctor_name',
                'm.medication_id',
                'm.medication_name',
                'm.description',
                'p.dosage',
                'p.form',
                'p.indication',
                'p.meal_rule',
                'p.notes',
                'p.status',
                'p.valid_from',
                'p.valid_until',
                'p.replaced_by',
                DB::raw("
                    CASE
                        WHEN p.status = 'Diganti' THEN 'Resep diperbarui'
                        WHEN p.status = 'Selesai' THEN 'Obat dihentikan'
                        ELSE 'Tidak aktif'
                    END as reason
                "),
                'p.created_at',
                'p.updated_at'
            )
            ->orderByDesc('p.updated_at')
            ->get();

        foreach ($data as $item) {
            $item->is_mine = $doctorId ? ((int) $item->doctor_id === (int) $doctorId) : false;

            $item->schedules = DB::table('prescription_schedules as ps')
                ->join('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
                ->where('ps.prescription_id', $item->prescription_id)
                ->select(
                    'ps.schedule_id',
                    'ps.session_id',
                    'ms.session_name',
                    'ms.start_time',
                    'ms.end_time',
                    'ms.default_reminder_time',
                    'ps.dose_per_session',
                    'ps.reminder_time',
                    'ps.is_active'
                )
                ->orderBy('ms.start_time')
                ->get();
        }

        return response()->json([
            'message' => 'Riwayat resep berhasil diambil',
            'data' => $data,
        ]);
    }

    public function store(Request $request, $patientId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'medication_id' => 'required|exists:medications,medication_id',
            'dosage' => 'required|string|max:100',
            'form' => ['required', 'string', 'max:100', Rule::in($this->dosageForms)],
            'indication' => 'nullable|string',
            'meal_rule' => ['nullable', 'string', 'max:100', Rule::in($this->mealRules)],
            'notes' => 'nullable|string',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
            'schedules' => 'required|array|min:1',
            'schedules.*.session_id' => 'required|exists:medication_sessions,session_id',
            'schedules.*.dose_per_session' => 'required|string|max:100',
            'schedules.*.reminder_time' => 'nullable|date_format:H:i',
        ]);

        return DB::transaction(function () use ($request, $patientId) {
            $prescriptionId = DB::table('prescriptions')->insertGetId([
                'patient_id' => $patientId,
                'doctor_id' => $request->doctor_id,
                'medication_id' => $request->medication_id,
                'dosage' => $request->dosage,
                'form' => $request->form,
                'indication' => $request->indication,
                'meal_rule' => $request->meal_rule,
                'notes' => $request->notes,
                'status' => 'Aktif',
                'valid_from' => $request->valid_from,
                'valid_until' => $request->valid_until,
                'replaced_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'prescription_id');

            foreach ($request->schedules as $schedule) {
                DB::table('prescription_schedules')->insert([
                    'prescription_id' => $prescriptionId,
                    'session_id' => $schedule['session_id'],
                    'dose_per_session' => $schedule['dose_per_session'],
                    'reminder_time' => $schedule['reminder_time'] ?? null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $medicationName = DB::table('medications')
                ->where('medication_id', $request->medication_id)
                ->value('medication_name');

            $patientName = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $patientId)
                ->value('u.full_name');

            $this->notifyPrescriptionChanged(
                $patientId,
                $prescriptionId,
                'Resep Obat Baru',

                "Dokter menambahkan resep {$medicationName}. Silakan cek jadwal minum obat Anda.",

                "Dokter menambahkan resep {$medicationName} untuk {$patientName}. Mohon bantu memantau jadwal minum obat pasien."
            );

            return response()->json([
                'message' => 'Resep obat berhasil ditambahkan',
                'data' => [
                    'prescription_id' => $prescriptionId,
                ],
            ], 201);
        });
    }

    public function update(Request $request, $prescriptionId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'patient_id' => 'required|exists:patients,patient_id',
            'medication_id' => 'required|exists:medications,medication_id',
            'dosage' => 'required|string|max:100',
            'form' => ['required', 'string', 'max:100', Rule::in($this->dosageForms)],
            'indication' => 'nullable|string',
            'meal_rule' => ['nullable', 'string', 'max:100', Rule::in($this->mealRules)],
            'notes' => 'nullable|string',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
            'schedules' => 'required|array|min:1',
            'schedules.*.session_id' => 'required|exists:medication_sessions,session_id',
            'schedules.*.dose_per_session' => 'required|string|max:100',
            'schedules.*.reminder_time' => 'nullable|date_format:H:i',
        ]);

        return DB::transaction(function () use ($request, $prescriptionId) {
            $old = DB::table('prescriptions')
                ->where('prescription_id', $prescriptionId)
                ->where('doctor_id', $request->doctor_id)
                ->where('status', 'Aktif')
                ->first();

            if (!$old) {
                return response()->json([
                    'message' => 'Resep aktif tidak ditemukan atau bukan milik dokter ini',
                ], 404);
            }

            $newPrescriptionId = DB::table('prescriptions')->insertGetId([
                'patient_id' => $request->patient_id,
                'doctor_id' => $request->doctor_id,
                'medication_id' => $request->medication_id,
                'dosage' => $request->dosage,
                'form' => $request->form,
                'indication' => $request->indication,
                'meal_rule' => $request->meal_rule,
                'notes' => $request->notes,
                'status' => 'Aktif',
                'valid_from' => $request->valid_from,
                'valid_until' => $request->valid_until,
                'replaced_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'prescription_id');

            foreach ($request->schedules as $schedule) {
                DB::table('prescription_schedules')->insert([
                    'prescription_id' => $newPrescriptionId,
                    'session_id' => $schedule['session_id'],
                    'dose_per_session' => $schedule['dose_per_session'],
                    'reminder_time' => $schedule['reminder_time'] ?? null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('prescriptions')
                ->where('prescription_id', $prescriptionId)
                ->update([
                    'status' => 'Diganti',
                    'valid_until' => now()->toDateString(),
                    'replaced_by' => $newPrescriptionId,
                    'updated_at' => now(),
                ]);

            DB::table('prescription_schedules')
                ->where('prescription_id', $prescriptionId)
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            $medicationName = DB::table('medications')
                ->where('medication_id', $request->medication_id)
                ->value('medication_name');

            $this->notifyPrescriptionChanged(
                $request->patient_id,
                $newPrescriptionId,
                'Resep Obat Diperbarui',

                "Dokter memperbarui resep {$medicationName}. Silakan cek jadwal minum obat terbaru.",

                "Dokter memperbarui resep {$medicationName} untuk {$patientName}. Mohon bantu memantau jadwal minum obat pasien."
            );

            return response()->json([
                'message' => 'Resep obat berhasil diperbarui',
                'data' => [
                    'old_prescription_id' => (int) $prescriptionId,
                    'new_prescription_id' => $newPrescriptionId,
                ],
            ]);
        });
    }

    public function stop(Request $request, $prescriptionId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'reason' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $prescriptionId) {
            $prescription = DB::table('prescriptions as p')
                ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
                ->where('p.prescription_id', $prescriptionId)
                ->where('p.doctor_id', $request->doctor_id)
                ->where('p.status', 'Aktif')
                ->select('p.*', 'm.medication_name')
                ->first();

            if (!$prescription) {
                return response()->json([
                    'message' => 'Resep aktif tidak ditemukan atau bukan milik dokter ini',
                ], 404);
            }

            $reason = $request->reason ?? 'Obat dihentikan oleh dokter';

            DB::table('prescriptions')
                ->where('prescription_id', $prescriptionId)
                ->update([
                    'status' => 'Selesai',
                    'valid_until' => now()->toDateString(),
                    'notes' => trim(($prescription->notes ?? '') . "\n" . $reason),
                    'updated_at' => now(),
                ]);

            DB::table('prescription_schedules')
                ->where('prescription_id', $prescriptionId)
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            $this->notifyPrescriptionChanged(
                $prescription->patient_id,
                $prescriptionId,
                'Resep Obat Dihentikan',

                "Dokter menghentikan resep {$prescription->medication_name}.",

                "Dokter menghentikan resep {$prescription->medication_name} untuk {$patientName}."
            );

            return response()->json([
                'message' => 'Resep obat berhasil dihentikan',
            ]);
        });
    }

    public function show(Request $request, $prescriptionId)
    {
        $doctorId = $request->query('doctor_id');

        $prescription = DB::table('prescriptions as p')
            ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->join('doctors as d', 'p.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('p.prescription_id', $prescriptionId)
            ->select(
                'p.prescription_id',
                'p.patient_id',
                'p.doctor_id',
                'u.full_name as doctor_name',
                'm.medication_id',
                'm.medication_name',
                'm.description',
                'p.dosage',
                'p.form',
                'p.indication',
                'p.meal_rule',
                'p.notes',
                'p.status',
                'p.valid_from',
                'p.valid_until',
                'p.replaced_by',
                'p.created_at',
                'p.updated_at'
            )
            ->first();

        if (!$prescription) {
            return response()->json([
                'message' => 'Resep tidak ditemukan',
            ], 404);
        }

        $prescription->is_mine = $doctorId ? ((int) $prescription->doctor_id === (int) $doctorId) : false;

        $prescription->schedules = DB::table('prescription_schedules as ps')
            ->join('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
            ->where('ps.prescription_id', $prescriptionId)
            ->select(
                'ps.schedule_id',
                'ps.session_id',
                'ms.session_name',
                'ms.start_time',
                'ms.end_time',
                'ms.default_reminder_time',
                'ps.dose_per_session',
                'ps.reminder_time',
                'ps.is_active'
            )
            ->orderBy('ms.start_time')
            ->get();

        return response()->json([
            'message' => 'Detail resep berhasil diambil',
            'data' => $prescription,
        ]);
    }
}
