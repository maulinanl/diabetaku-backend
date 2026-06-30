<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClinicalNoteController extends Controller
{
    private function activeRelationId(int $doctorId, int $patientId): ?int
    {
        $id = DB::table('doctor_patient_relations')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->where('status', 'Diterima')
            ->value('doctor_patient_relation_id');

        return $id === null ? null : (int) $id;
    }

    public function store(Request $request, $patientId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'patient_condition' => 'required|string',
            'doctor_note' => 'required|string',
            'treatment_plan' => 'required|string',
            'follow_up_date' => 'nullable|date',
        ]);

        $relationId = $this->activeRelationId((int) $request->doctor_id, (int) $patientId);

        if (!$relationId) {
            return response()->json([
                'message' => 'Relasi dokter dan pasien aktif tidak ditemukan'
            ], 404);
        }

        $id = DB::table('clinical_notes')->insertGetId([
            'doctor_patient_relation_id' => $relationId,
            'patient_condition' => $request->patient_condition,
            'doctor_note' => $request->doctor_note,
            'treatment_plan' => $request->treatment_plan,
            'follow_up_date' => $request->follow_up_date,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'clinical_note_id');

        return response()->json([
            'message' => 'Catatan klinis berhasil disimpan',
            'data' => [
                'clinical_note_id' => $id,
                'doctor_patient_relation_id' => $relationId,
            ]
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $notes = DB::table('clinical_notes as cn')
            ->join('doctor_patient_relations as dpr', 'cn.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('dpr.patient_id', $patientId)
            ->select(
                'cn.*',
                'dpr.patient_id',
                'dpr.doctor_id',
                'u.full_name as doctor_name'
            )
            ->orderByDesc('cn.created_at')
            ->get();

        return response()->json([
            'message' => 'Catatan klinis berhasil diambil',
            'data' => $notes
        ]);
    }

    public function show($clinicalNoteId)
    {
        $note = DB::table('clinical_notes as cn')
            ->join('doctor_patient_relations as dpr', 'cn.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as du', 'd.user_id', '=', 'du.user_id')
            ->join('patients as p', 'dpr.patient_id', '=', 'p.patient_id')
            ->join('users as pu', 'p.user_id', '=', 'pu.user_id')
            ->where('cn.clinical_note_id', $clinicalNoteId)
            ->select(
                'cn.*',
                'dpr.doctor_id',
                'dpr.patient_id',
                'du.full_name as doctor_name',
                'pu.full_name as patient_name'
            )
            ->first();

        if (!$note) {
            return response()->json([
                'message' => 'Catatan klinis tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail catatan klinis berhasil diambil',
            'data' => $note
        ]);
    }
}
