<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClinicalNoteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'patient_id' => 'required|exists:patients,patient_id',
            'patient_condition' => 'required|string',
            'doctor_note' => 'required|string',
            'treatment_plan' => 'required|string',
            'follow_up_date' => 'nullable|date'
        ]);

        $id = DB::table('clinical_notes')->insertGetId([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'patient_condition' => $request->patient_condition,
            'doctor_note' => $request->doctor_note,
            'treatment_plan' => $request->treatment_plan,
            'follow_up_date' => $request->follow_up_date,
            'created_at' => now(),
            'updated_at' => now()
        ], 'clinical_note_id');

        return response()->json([
            'message' => 'Catatan klinis berhasil disimpan',
            'clinical_note_id' => $id
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $notes = DB::table('clinical_notes')
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Catatan klinis berhasil diambil',
            'data' => $notes
        ]);
    }
}
