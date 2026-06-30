<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function pending()
    {
        $data = DB::table('doctors as d')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->where('d.verification_status', 'Menunggu')
            ->select(
                'd.doctor_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                's.specialization_name',
                'd.str_number',
                'd.institution',
                'd.verification_status',
                'd.created_at'
            )
            ->orderByDesc('d.created_at')
            ->get();

        return response()->json([
            'message' => 'Daftar dokter menunggu verifikasi berhasil diambil',
            'data' => $data
        ]);
    }

    public function verify($doctorId)
    {
        $doctor = DB::table('doctors')->where('doctor_id', $doctorId)->first();

        if (!$doctor) {
            return response()->json(['message' => 'Dokter tidak ditemukan'], 404);
        }

        DB::table('doctors')->where('doctor_id', $doctorId)->update([
            'verification_status' => 'Disetujui',
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('user_id', $doctor->user_id)->update([
            'account_status' => 'Aktif',
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Dokter berhasil diverifikasi'
        ]);
    }

    public function reject($doctorId)
    {
        $doctor = DB::table('doctors')->where('doctor_id', $doctorId)->first();

        if (!$doctor) {
            return response()->json(['message' => 'Dokter tidak ditemukan'], 404);
        }

        DB::table('doctors')->where('doctor_id', $doctorId)->update([
            'verification_status' => 'Ditolak',
            'verified_at' => null,
            'updated_at' => now(),
        ]);

        DB::table('users')->where('user_id', $doctor->user_id)->update([
            'account_status' => 'Tidak Aktif',
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Dokter berhasil ditolak'
        ]);
    }
}
