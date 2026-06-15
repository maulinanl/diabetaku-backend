<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClinicalNoteController extends Controller
{
    public function store(Request $request, $patientId)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'patient_condition' => 'required|string',
            'doctor_note' => 'required|string',
            'treatment_plan' => 'required|string',
            'follow_up_date' => 'nullable|date',
        ]);

        $id = DB::table('clinical_notes')->insertGetId([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $patientId,
            'patient_condition' => $request->patient_condition,
            'doctor_note' => $request->doctor_note,
            'treatment_plan' => $request->treatment_plan,
            'follow_up_date' => $request->follow_up_date,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'clinical_note_id');

        return response()->json([
            'message' => 'Catatan klinis berhasil disimpan',
            'clinical_note_id' => $id
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $notes = DB::table('clinical_notes as cn')
            ->join('doctors as d', 'cn.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->where('cn.patient_id', $patientId)
            ->select(
                'cn.*',
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
            ->join('doctors as d', 'cn.doctor_id', '=', 'd.doctor_id')
            ->join('users as du', 'd.user_id', '=', 'du.user_id')
            ->join('patients as p', 'cn.patient_id', '=', 'p.patient_id')
            ->join('users as pu', 'p.user_id', '=', 'pu.user_id')
            ->where('cn.clinical_note_id', $clinicalNoteId)
            ->select(
                'cn.*',
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
