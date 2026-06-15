<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicationConsumptionLogController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'prescription_id' => 'required|exists:prescriptions,prescription_id',
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'schedule_id' => 'required|exists:prescription_schedules,schedule_id',
            'log_date' => 'required|date',
            'status' => 'required|in:Diminum,Terlewat,Dibatalkan',
            'note' => 'nullable|string',
        ]);

        $id = DB::table('medication_consumption_logs')->insertGetId([
            'prescription_id' => $request->prescription_id,
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'schedule_id' => $request->schedule_id,
            'log_date' => $request->log_date,
            'status' => $request->status,
            'checked_at' => $request->status === 'Diminum' ? now() : null,
            'cancelled_at' => $request->status === 'Dibatalkan' ? now() : null,
            'note' => $request->note,
            'validation_status' => 'Valid',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'log_id');

        return response()->json([
            'message' => 'Log konsumsi obat berhasil ditambahkan',
            'log_id' => $id
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $logs = DB::table('medication_consumption_logs as mcl')
            ->join('prescriptions as p', 'mcl.prescription_id', '=', 'p.prescription_id')
            ->join('prescription_schedules as ps', 'mcl.schedule_id', '=', 'ps.schedule_id')
            ->where('mcl.patient_id', $patientId)
            ->select(
                'mcl.*',
                'p.drug_name',
                'p.dosage',
                'p.form',
                'ps.session',
                'ps.dose_per_session'
            )
            ->orderByDesc('mcl.log_date')
            ->get();

        return response()->json([
            'message' => 'Log konsumsi obat berhasil diambil',
            'data' => $logs
        ]);
    }
}
