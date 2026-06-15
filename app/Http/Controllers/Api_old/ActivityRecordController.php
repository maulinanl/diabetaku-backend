<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityRecordController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'activity_type_id' => 'required|exists:activity_types,activity_type_id',
            'duration_minutes' => 'required|integer|min:1',
            'intensity' => 'required|in:Ringan,Sedang,Berat',
            'activity_date' => 'required|date',
        ]);

        $id = DB::table('activity_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'activity_type_id' => $request->activity_type_id,
            'duration_minutes' => $request->duration_minutes,
            'intensity' => $request->intensity,
            'validation_status' => 'Valid',
            'activity_date' => $request->activity_date,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'activity_id');

        return response()->json([
            'message' => 'Data aktivitas berhasil ditambahkan',
            'activity_id' => $id
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $records = DB::table('activity_records as ar')
            ->join('activity_types as at', 'ar.activity_type_id', '=', 'at.activity_type_id')
            ->where('ar.patient_id', $patientId)
            ->select(
                'ar.*',
                'at.activity_name'
            )
            ->orderByDesc('activity_date')
            ->get();

        return response()->json([
            'message' => 'Data aktivitas berhasil diambil',
            'data' => $records
        ]);
    }
}
