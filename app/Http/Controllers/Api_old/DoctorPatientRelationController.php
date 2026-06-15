<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorPatientRelationController extends Controller
{
    public function requestConnection(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        DB::table('doctor_patient_relations')->updateOrInsert(
            [
                'doctor_id' => $request->doctor_id,
                'patient_id' => $request->patient_id,
            ],
            [
                'status' => 'Menunggu',
                'requested_at' => now(),
                'responded_at' => null,
                'connected_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Permintaan koneksi berhasil dikirim'
        ], 201);
    }

    public function acceptConnection(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        DB::table('doctor_patient_relations')
            ->where('doctor_id', $request->doctor_id)
            ->where('patient_id', $request->patient_id)
            ->update([
                'status' => 'Diterima',
                'responded_at' => now(),
                'connected_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Permintaan koneksi diterima'
        ]);
    }

    public function rejectConnection(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        DB::table('doctor_patient_relations')
            ->where('doctor_id', $request->doctor_id)
            ->where('patient_id', $request->patient_id)
            ->update([
                'status' => 'Ditolak',
                'responded_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Permintaan koneksi ditolak'
        ]);
    }

    public function disconnectConnection(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        $updated = DB::table('doctor_patient_relations')
            ->where('doctor_id', $request->doctor_id)
            ->where('patient_id', $request->patient_id)
            ->where('status', 'Diterima')
            ->update([
                'status' => 'Diputus',
                'responded_at' => now(),
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'message' => 'Relasi aktif tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Relasi dokter dan pasien berhasil diputus'
        ]);
    }
}
