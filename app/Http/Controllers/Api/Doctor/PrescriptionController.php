<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrescriptionController extends Controller
{
    private function mealRuleRule()
    {
        return Rule::in(['Sebelum Makan', 'Sesudah Makan', 'Saat Makan', 'Sebelum Tidur', 'Bangun Tidur', 'Bebas']);
    }

    private function parseQuantity(?string $dosage, $quantity = null): ?float
    {
        if ($quantity !== null && $quantity !== '') {
            return (float) $quantity;
        }

        if (!$dosage) {
            return null;
        }

        if (preg_match('/\d+(?:[\.,]\d+)?/', $dosage, $matches)) {
            return (float) str_replace(',', '.', $matches[0]);
        }

        return null;
    }

    private function quantityUnit(?string $dosage, ?string $form, $quantityUnit = null): ?string
    {
        if ($quantityUnit !== null && trim((string) $quantityUnit) !== '') {
            return trim((string) $quantityUnit);
        }

        $dosage = trim((string) $dosage);
        $form = trim((string) $form);

        if ($dosage !== '') {
            return $form !== '' ? trim($dosage . ' ' . $form) : $dosage;
        }

        return $form !== '' ? $form : null;
    }

    private function activeRelationId(int $doctorId, int $patientId): ?int
    {
        $id = DB::table('doctor_patient_relations')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->where('status', 'Diterima')
            ->value('doctor_patient_relation_id');

        return $id === null ? null : (int) $id;
    }

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

        $sendPushNotification = function () use ($userId, $title, $message, $notificationId, $referenceId, $referenceType, $typeId) {
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

    private function notifyPrescriptionChanged($patientId, $prescriptionId, $title, $patientMessage, $caregiverMessage = null, $referenceType = 'prescription')
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
            $referenceType
        );

        $caregiverUserIds = DB::table('caregiver_patient_relations as cpr')
            ->join('caregivers as c', 'cpr.caregiver_id', '=', 'c.caregiver_id')
            ->where('cpr.patient_id', $patientId)
            ->where('cpr.status', 'Diterima')
            ->pluck('c.user_id');

        foreach ($caregiverUserIds as $userId) {
            $this->createNotification(
                $userId,
                'Pengingat Obat',
                $title,
                $caregiverMessage ?? $patientMessage,
                $prescriptionId,
                $referenceType
            );
        }
    }

    private function prescriptionBaseQuery()
    {
        return DB::table('prescriptions as p')
            ->join('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id');
    }

    private function prescriptionSelect(): array
    {
        return [
            'p.prescription_id',
            'dpr.patient_id',
            'dpr.doctor_id',
            'p.doctor_patient_relation_id',
            'u.full_name as doctor_name',
            'm.medication_id',
            'm.medication_name',
            'm.description',
            'm.dosage_form as form',
            DB::raw("TRIM(COALESCE(p.quantity::text, '') || ' ' || COALESCE(p.quantity_unit, '')) as dosage"),
            'p.quantity',
            'p.quantity_unit',
            DB::raw('NULL::text as indication'),
            'p.meal_rule',
            'p.notes',
            'p.status_prescription as status',
            'p.start_date',
            'p.end_date',
            'p.start_date as valid_from',
            'p.end_date as valid_until',
            'p.replaced_by',
            'p.created_at',
            'p.updated_at',
        ];
    }

    private function attachSchedules($item): void
    {
        $item->schedules = DB::table('prescription_schedules as ps')
            ->join('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
            ->where('ps.prescription_id', $item->prescription_id)
            ->select(
                'ps.prescription_schedule_id as schedule_id',
                'ps.prescription_schedule_id',
                'ps.session_id',
                'ms.session_name',
                'ms.start_time',
                'ms.end_time',
                'ms.default_reminder_time',
                DB::raw('ms.default_reminder_time as reminder_time'),
                DB::raw("TRIM(COALESCE((SELECT quantity::text FROM prescriptions WHERE prescriptions.prescription_id = ps.prescription_id), '') || ' ' || COALESCE((SELECT quantity_unit FROM prescriptions WHERE prescriptions.prescription_id = ps.prescription_id), '')) as dose_per_session"),
                DB::raw('true as is_active')
            )
            ->orderBy('ms.start_time')
            ->get();
    }

    public function searchMedications(Request $request)
    {
        $keyword = trim($request->query('keyword', ''));

        $data = DB::table('medications')
            ->where('is_active', true)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where('medication_name', 'ILIKE', "%{$keyword}%");
            })
            ->select(
                'medication_id',
                'medication_name',
                'dosage_form',
                'value',
                'unit',
                'description'
            )
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
            ->select('session_id', 'session_name', 'start_time', 'end_time', 'default_reminder_time')
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

        $data = $this->prescriptionBaseQuery()
            ->where('dpr.patient_id', $patientId)
            ->where('p.status_prescription', 'Aktif')
            ->where(function ($query) {
                $query->whereNull('p.start_date')->orWhereDate('p.start_date', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('p.end_date')->orWhereDate('p.end_date', '>=', now()->toDateString());
            })
            ->select($this->prescriptionSelect())
            ->orderByDesc('p.created_at')
            ->get();

        foreach ($data as $item) {
            $item->is_mine = $doctorId ? ((int) $item->doctor_id === (int) $doctorId) : false;
            $this->attachSchedules($item);
        }

        return response()->json([
            'message' => 'Resep aktif berhasil diambil',
            'data' => $data,
        ]);
    }

    public function history(Request $request, $patientId)
    {
        $doctorId = $request->query('doctor_id');

        $data = $this->prescriptionBaseQuery()
            ->where('dpr.patient_id', $patientId)
            ->whereIn('p.status_prescription', [
                'Selesai',
                'Diganti',
                'Dihentikan',
            ])
            ->select(array_merge($this->prescriptionSelect(), [
                DB::raw("\n                    CASE\n                        WHEN p.status_prescription = 'Diganti' THEN 'Resep diperbarui'\n                        WHEN p.status_prescription = 'Selesai' THEN 'Resep selesai'\n                        WHEN p.status_prescription = 'Dihentikan' THEN 'Obat dihentikan'\n                        ELSE 'Tidak aktif'\n                    END as reason\n                "),
            ]))
            ->orderByDesc('p.updated_at')
            ->get();

        foreach ($data as $item) {
            $item->is_mine = $doctorId ? ((int) $item->doctor_id === (int) $doctorId) : false;
            $this->attachSchedules($item);
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
            'dosage' => 'nullable|string|max:100',
            'form' => ['nullable', 'string', 'max:100', Rule::in(['Tablet', 'Kapsul', 'Sirup', 'Injeksi', 'Tetes', 'Krim/Salep'])],
            'quantity' => 'nullable|numeric',
            'quantity_unit' => 'nullable|string|max:50',
            'indication' => 'nullable|string',
            'meal_rule' => ['nullable', 'string', 'max:100', $this->mealRuleRule()],
            'notes' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'schedules' => 'required|array|min:1',
            'schedules.*.session_id' => 'required|exists:medication_sessions,session_id',
        ]);

        return DB::transaction(function () use ($request, $patientId) {
            $relationId = $this->activeRelationId((int) $request->doctor_id, (int) $patientId);

            if (!$relationId) {
                return response()->json([
                    'message' => 'Relasi dokter dan pasien aktif tidak ditemukan',
                ], 404);
            }

            $prescriptionId = DB::table('prescriptions')->insertGetId([
                'doctor_patient_relation_id' => $relationId,
                'medication_id' => $request->medication_id,
                'quantity' => $this->parseQuantity($request->dosage, $request->quantity),
                'quantity_unit' => $this->quantityUnit($request->dosage, $request->form, $request->quantity_unit),
                'meal_rule' => $request->meal_rule,
                'notes' => $request->notes,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status_prescription' => 'Aktif',
                'replaced_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'prescription_id');

            foreach ($request->schedules as $schedule) {
                DB::table('prescription_schedules')->insert([
                    'prescription_id' => $prescriptionId,
                    'session_id' => $schedule['session_id'],
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
                "Dokter menambahkan resep {$medicationName} untuk {$patientName}. Mohon bantu memantau jadwal minum obat pasien.",
                'prescription_created'
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
            'dosage' => 'nullable|string|max:100',
            'form' => ['nullable', 'string', 'max:100', Rule::in(['Tablet', 'Kapsul', 'Sirup', 'Injeksi', 'Tetes', 'Krim/Salep'])],
            'quantity' => 'nullable|numeric',
            'quantity_unit' => 'nullable|string|max:50',
            'indication' => 'nullable|string',
            'meal_rule' => ['nullable', 'string', 'max:100', $this->mealRuleRule()],
            'notes' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'schedules' => 'required|array|min:1',
            'schedules.*.session_id' => 'required|exists:medication_sessions,session_id',
        ]);

        return DB::transaction(function () use ($request, $prescriptionId) {
            $old = $this->prescriptionBaseQuery()
                ->where('p.prescription_id', $prescriptionId)
                ->where('dpr.doctor_id', $request->doctor_id)
                ->where('p.status_prescription', 'Aktif')
                ->select('p.*', 'dpr.patient_id', 'dpr.doctor_id')
                ->first();

            if (!$old) {
                return response()->json([
                    'message' => 'Resep aktif tidak ditemukan atau bukan milik dokter ini',
                ], 404);
            }

            $relationId = $this->activeRelationId((int) $request->doctor_id, (int) $request->patient_id);

            if (!$relationId) {
                return response()->json([
                    'message' => 'Relasi dokter dan pasien aktif tidak ditemukan',
                ], 404);
            }

            $newPrescriptionId = DB::table('prescriptions')->insertGetId([
                'doctor_patient_relation_id' => $relationId,
                'medication_id' => $request->medication_id,
                'quantity' => $this->parseQuantity($request->dosage, $request->quantity),
                'quantity_unit' => $this->quantityUnit($request->dosage, $request->form, $request->quantity_unit),
                'meal_rule' => $request->meal_rule,
                'notes' => $request->notes,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status_prescription' => 'Aktif',
                'replaced_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'prescription_id');

            foreach ($request->schedules as $schedule) {
                DB::table('prescription_schedules')->insert([
                    'prescription_id' => $newPrescriptionId,
                    'session_id' => $schedule['session_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('prescriptions')
                ->where('prescription_id', $prescriptionId)
                ->update([
                    'status_prescription' => 'Diganti',
                    'end_date' => now()->toDateString(),
                    'replaced_by' => $newPrescriptionId,
                    'updated_at' => now(),
                ]);

            $medicationName = DB::table('medications')
                ->where('medication_id', $request->medication_id)
                ->value('medication_name');

            $patientName = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $request->patient_id)
                ->value('u.full_name');

            $this->notifyPrescriptionChanged(
                $request->patient_id,
                $newPrescriptionId,
                'Resep Obat Diperbarui',
                "Dokter memperbarui resep {$medicationName}. Silakan cek jadwal minum obat terbaru.",
                "Dokter memperbarui resep {$medicationName} untuk {$patientName}. Mohon bantu memantau jadwal minum obat pasien.",
                'prescription_updated'
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
            $prescription = $this->prescriptionBaseQuery()
                ->where('p.prescription_id', $prescriptionId)
                ->where('dpr.doctor_id', $request->doctor_id)
                ->where('p.status_prescription', 'Aktif')
                ->select('p.*', 'dpr.patient_id', 'm.medication_name')
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
                    'status_prescription' => 'Dihentikan',
                    'end_date' => now()->toDateString(),
                    'notes' => trim(($prescription->notes ?? '') . "\n" . $reason),
                    'updated_at' => now(),
                ]);

            $patientName = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $prescription->patient_id)
                ->value('u.full_name');

            $this->notifyPrescriptionChanged(
                $prescription->patient_id,
                $prescriptionId,
                'Resep Obat Dihentikan',
                "Dokter menghentikan resep {$prescription->medication_name}.",
                "Dokter menghentikan resep {$prescription->medication_name} untuk {$patientName}.",
                'prescription_stopped'
            );

            return response()->json([
                'message' => 'Resep obat berhasil dihentikan',
            ]);
        });
    }

    public function show(Request $request, $prescriptionId)
    {
        $doctorId = $request->query('doctor_id');

        $prescription = $this->prescriptionBaseQuery()
            ->where('p.prescription_id', $prescriptionId)
            ->select($this->prescriptionSelect())
            ->first();

        if (!$prescription) {
            return response()->json([
                'message' => 'Resep tidak ditemukan',
            ], 404);
        }

        $prescription->is_mine = $doctorId ? ((int) $prescription->doctor_id === (int) $doctorId) : false;
        $this->attachSchedules($prescription);

        return response()->json([
            'message' => 'Detail resep berhasil diambil',
            'data' => $prescription,
        ]);
    }
}
