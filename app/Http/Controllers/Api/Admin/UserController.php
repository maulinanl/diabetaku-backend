<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $data = DB::table('users as u')
            ->join('roles as r', 'u.role_id', '=', 'r.role_id')
            ->select(
                'u.user_id',
                'r.role_name',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'u.account_status',
                'u.created_at'
            )
            ->orderByDesc('u.created_at')
            ->get();

        return response()->json([
            'message' => 'Data pengguna berhasil diambil',
            'data' => $data
        ]);
    }

    public function updateStatus(Request $request, $userId)
    {
        $request->validate([
            'account_status' => 'required|in:Menunggu Verifikasi,Aktif,Tidak Aktif,Terkunci',
        ]);

        $updated = DB::table('users')
            ->where('user_id', $userId)
            ->update([
                'account_status' => $request->account_status,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'message' => 'Data pengguna tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Status pengguna berhasil diperbarui'
        ]);
    }

    public function dashboard()
    {
        $data = [
            'total_users' => DB::table('users')->count(),
            'total_doctors' => DB::table('doctors')->count(),
            'total_patients' => DB::table('patients')->count(),
            'total_families' => DB::table('families')->count(),
            'pending_doctors' => DB::table('doctors')
                ->where('verification_status', 'Menunggu')
                ->count(),
        ];

        return response()->json([
            'message' => 'Dashboard admin berhasil diambil',
            'data' => $data
        ]);
    }
}
