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
            ->leftJoin('roles as r', 'u.role_id', '=', 'r.role_id')
            ->select(
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'u.account_status',
                'r.role_name',
                'u.created_at'
            )
            ->orderByDesc('u.created_at')
            ->get();

        return response()->json([
            'message' => 'Daftar pengguna berhasil diambil',
            'data' => $data
        ]);
    }

    public function updateStatus(Request $request, $userId)
    {
        $request->validate([
            'account_status' => 'required|in:Aktif,Nonaktif,Diblokir',
        ]);

        $updated = DB::table('users')
            ->where('user_id', $userId)
            ->update([
                'account_status' => $request->account_status,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        return response()->json([
            'message' => 'Status pengguna berhasil diperbarui'
        ]);
    }

    public function dashboard()
    {
        return response()->json([
            'message' => 'Dashboard admin berhasil diambil',
            'data' => [
                'total_users' => DB::table('users')->count(),
                'total_patients' => DB::table('patients')->count(),
                'total_doctors' => DB::table('doctors')->count(),
                'pending_doctors' => DB::table('doctors')
                    ->where('verification_status', 'Menunggu')
                    ->count(),
            ]
        ]);
    }
}
