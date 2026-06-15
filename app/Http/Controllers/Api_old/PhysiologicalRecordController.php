<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhysiologicalRecordController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'systolic' => 'nullable|integer',
            'diastolic' => 'nullable|integer',
            'weight_kg' => 'nullable|numeric',
            'bmi' => 'nullable|numeric',
            'measured_at' => 'required|date',
        ]);

        $id = DB::table('physiological_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'systolic' => $request->systolic,
            'diastolic' => $request->diastolic,
            'weight_kg' => $request->weight_kg,
            'bmi' => $request->bmi,
            'validation_status' => 'Valid',
            'measured_at' => $request->measured_at,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'physiological_id');

        return response()->json([
            'message' => 'Data fisiologis berhasil ditambahkan',
            'physiological_id' => $id
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $records = DB::table('physiological_records')
            ->where('patient_id', $patientId)
            ->orderByDesc('measured_at')
            ->get();

        return response()->json([
            'message' => 'Data fisiologis berhasil diambil',
            'data' => $records
        ]);
    }
}
