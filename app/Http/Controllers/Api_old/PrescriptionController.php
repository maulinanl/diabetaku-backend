<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'drug_name' => 'required|string|max:100',
            'dosage' => 'required|string|max:50',
            'form' => 'required|in:Tablet,Kapsul,Sirup,Injeksi,Lainnya',
            'indication' => 'nullable|string|max:255',
            'meal_rule' => 'required|in:Sebelum Makan,Sesudah Makan,Bersama Makan,Bebas',
            'notes' => 'nullable|string',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date'
        ]);

        $id = DB::table('prescriptions')->insertGetId([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'drug_name' => $request->drug_name,
            'dosage' => $request->dosage,
            'form' => $request->form,
            'indication' => $request->indication,
            'meal_rule' => $request->meal_rule,
            'notes' => $request->notes,
            'status' => 'Aktif',
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'created_at' => now(),
            'updated_at' => now()
        ], 'prescription_id');

        return response()->json([
            'message' => 'Resep berhasil ditambahkan',
            'prescription_id' => $id
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $prescriptions = DB::table('prescriptions')
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Data resep berhasil diambil',
            'data' => $prescriptions
        ]);
    }
}
