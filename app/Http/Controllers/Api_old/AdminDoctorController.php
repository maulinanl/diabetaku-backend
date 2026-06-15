<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDoctorController extends Controller
{
    public function approveDoctor(Request $request, $doctorId)
    {
        $adminId = $request->input('admin_id');

        $doctor = DB::table('doctors')->where('doctor_id', $doctorId)->first();

        if (!$doctor) {
            return response()->json([
                'message' => 'Data dokter tidak ditemukan'
            ], 404);
        }

        DB::transaction(function () use ($doctor, $doctorId, $adminId) {
            DB::table('doctors')
                ->where('doctor_id', $doctorId)
                ->update([
                    'verification_status' => 'Disetujui',
                    'verified_by_admin_id' => $adminId,
                    'verified_at' => now(),
                    'rejection_reason' => null,
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('user_id', $doctor->user_id)
                ->update([
                    'account_status' => 'Aktif',
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'message' => 'Dokter berhasil diverifikasi'
        ]);
    }

    public function rejectDoctor(Request $request, $doctorId)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,admin_id',
            'rejection_reason' => 'required|string',
        ]);

        $doctor = DB::table('doctors')->where('doctor_id', $doctorId)->first();

        if (!$doctor) {
            return response()->json([
                'message' => 'Data dokter tidak ditemukan'
            ], 404);
        }

        DB::transaction(function () use ($doctor, $doctorId, $request) {
            DB::table('doctors')
                ->where('doctor_id', $doctorId)
                ->update([
                    'verification_status' => 'Ditolak',
                    'verified_by_admin_id' => $request->admin_id,
                    'verified_at' => now(),
                    'rejection_reason' => $request->rejection_reason,
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('user_id', $doctor->user_id)
                ->update([
                    'account_status' => 'Tidak Aktif',
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'message' => 'Registrasi dokter ditolak'
        ]);
    }
}
