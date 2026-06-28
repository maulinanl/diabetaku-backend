<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PrescriptionLifecycleService
{
    public function finishActivePrescriptionsForRelation(
        int $doctorId,
        int $patientId,
        ?string $reason = null
    ): int {
        $now = now();
        $today = $now->toDateString();
        $stopReason = $reason ?? 'Relasi dokter-pasien terputus';

        $prescriptions = DB::table('prescriptions')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->where('status', 'Aktif')
            ->select('prescription_id', 'notes')
            ->get();

        if ($prescriptions->isEmpty()) {
            return 0;
        }

        $prescriptionIds = $prescriptions
            ->pluck('prescription_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        foreach ($prescriptions as $prescription) {
            $oldNotes = trim((string) ($prescription->notes ?? ''));
            $newNotes = str_contains($oldNotes, $stopReason)
                ? $oldNotes
                : trim($oldNotes . "\n" . $stopReason);

            DB::table('prescriptions')
                ->where('prescription_id', $prescription->prescription_id)
                ->update([
                    'status' => 'Selesai',
                    'valid_until' => $today,
                    'notes' => $newNotes === '' ? $stopReason : $newNotes,
                    'updated_at' => $now,
                ]);
        }

        DB::table('prescription_schedules')
            ->whereIn('prescription_id', $prescriptionIds)
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);

        return $prescriptions->count();
    }
}
