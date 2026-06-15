<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescriptionScheduleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'prescription_id' => 'required|exists:prescriptions,prescription_id',
            'session' => 'required|in:Pagi,Siang,Sore,Malam',
            'dose_per_session' => 'required|string|max:50',
        ]);

        $id = DB::table('prescription_schedules')->insertGetId([
            'prescription_id' => $request->prescription_id,
            'session' => $request->session,
            'dose_per_session' => $request->dose_per_session,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'schedule_id');

        return response()->json([
            'message' => 'Jadwal resep berhasil ditambahkan',
            'schedule_id' => $id
        ], 201);
    }

    public function getByPrescription($prescriptionId)
    {
        $schedules = DB::table('prescription_schedules')
            ->where('prescription_id', $prescriptionId)
            ->orderBy('schedule_id')
            ->get();

        return response()->json([
            'message' => 'Jadwal resep berhasil diambil',
            'data' => $schedules
        ]);
    }
}
