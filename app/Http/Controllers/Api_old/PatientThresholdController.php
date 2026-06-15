<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientThresholdController extends Controller
{
    public function getByPatient($patientId)
    {
        $data = DB::table('clinical_parameters as cp')
            ->leftJoin('patient_custom_thresholds as pct', function ($join) use ($patientId) {
                $join->on('cp.parameter_id', '=', 'pct.parameter_id')
                    ->where('pct.patient_id', '=', $patientId);
            })
            ->select(
                'cp.parameter_id',
                'cp.parameter_name',
                'cp.default_min',
                'cp.default_max',
                'cp.unit',
                'pct.custom_min',
                'pct.custom_max',
                'pct.set_by_doctor_id'
            )
            ->orderBy('cp.parameter_id')
            ->get();

        return response()->json([
            'message' => 'Batas normal pasien berhasil diambil',
            'data' => $data
        ]);
    }

    public function updateOrCreate(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'parameter_id' => 'required|exists:clinical_parameters,parameter_id',
            'set_by_doctor_id' => 'required|exists:doctors,doctor_id',
            'custom_min' => 'nullable|numeric',
            'custom_max' => 'nullable|numeric',
        ]);

        DB::table('patient_custom_thresholds')->updateOrInsert(
            [
                'patient_id' => $request->patient_id,
                'parameter_id' => $request->parameter_id,
            ],
            [
                'set_by_doctor_id' => $request->set_by_doctor_id,
                'custom_min' => $request->custom_min,
                'custom_max' => $request->custom_max,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Batas normal pasien berhasil disimpan'
        ]);
    }

    public function reset($patientId, $parameterId)
    {
        DB::table('patient_custom_thresholds')
            ->where('patient_id', $patientId)
            ->where('parameter_id', $parameterId)
            ->delete();

        return response()->json([
            'message' => 'Batas normal pasien berhasil dikembalikan ke default'
        ]);
    }
}
