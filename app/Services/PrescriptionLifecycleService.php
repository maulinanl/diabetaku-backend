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

        $prescriptions = DB::table('prescriptions as p')
            ->join('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->where('dpr.doctor_id', $doctorId)
            ->where('dpr.patient_id', $patientId)
            ->where('p.status_prescription', 'Aktif')
            ->select('p.prescription_id', 'p.notes')
            ->get();

        if ($prescriptions->isEmpty()) {
            return 0;
        }

        foreach ($prescriptions as $prescription) {
            $oldNotes = trim((string) ($prescription->notes ?? ''));
            $newNotes = str_contains($oldNotes, $stopReason)
                ? $oldNotes
                : trim($oldNotes . "\n" . $stopReason);

            DB::table('prescriptions')
                ->where('prescription_id', $prescription->prescription_id)
                ->update([
                    'status_prescription' => 'Selesai',
                    'end_date' => $today,
                    'notes' => $newNotes === '' ? $stopReason : $newNotes,
                    'updated_at' => $now,
                ]);
        }

        return $prescriptions->count();
    }
}
