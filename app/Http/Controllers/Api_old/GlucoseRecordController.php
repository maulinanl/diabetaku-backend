<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlucoseRecordController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'measurement_type' => 'required|in:Puasa,Dua Jam Setelah Makan,Sewaktu',
            'glucose_value' => 'required|numeric',
            'measured_at' => 'required|date',
        ]);

        $glucoseId = DB::table('glucose_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'measurement_type' => $request->measurement_type,
            'glucose_value' => $request->glucose_value,
            'validation_status' => 'Valid',
            'measured_at' => $request->measured_at,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'glucose_id');

        return response()->json([
            'message' => 'Data gula darah berhasil ditambahkan',
            'glucose_id' => $glucoseId
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $records = DB::table('glucose_records')
            ->where('patient_id', $patientId)
            ->orderByDesc('measured_at')
            ->get();

        return response()->json([
            'message' => 'Data gula darah berhasil diambil',
            'data' => $records
        ]);
    }
}
