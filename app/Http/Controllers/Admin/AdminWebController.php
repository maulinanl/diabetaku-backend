<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class AdminWebController extends Controller
{
    public function dashboard()
    {
        $totalUsers = DB::table('users')->count();
        $totalPatients = DB::table('patients')->count();
        $totalDoctors = DB::table('doctors')->count();
        $totalCaregivers = DB::table('caregivers')->count();
        $activeUsers = DB::table('users')
            ->where('account_status', 'Aktif')
            ->count();

        $pendingDoctors = DB::table('doctors')
            ->where('verification_status', 'Menunggu')
            ->count();

        $verifiedDoctors = DB::table('doctors')
            ->where('verification_status', 'Disetujui')
            ->count();

        $latestUsers = DB::table('users as u')
            ->leftJoin('roles as r', 'u.role_id', '=', 'r.role_id')
            ->select(
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.account_status',
                'r.role_name',
                'u.created_at'
            )
            ->orderByDesc('u.created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalPatients',
            'totalDoctors',
            'pendingDoctors',
            'verifiedDoctors',
            'totalCaregivers',
            'activeUsers',
            'latestUsers'
        ));
    }

    public function pendingDoctors()
    {
        $doctors = DB::table('doctors as d')
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

        return view('admin.doctors.pending', compact('doctors'));
    }

    public function verifyDoctor($doctorId)
    {
        $doctor = DB::table('doctors')
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$doctor) {
            return back()->with('error', 'Data dokter tidak ditemukan.');
        }

        $adminId = DB::table('admins')
            ->where('user_id', session('admin_id'))
            ->value('admin_id');

        DB::transaction(function () use ($doctor, $adminId) {
            DB::table('doctors')
                ->where('doctor_id', $doctor->doctor_id)
                ->update([
                    'verification_status' => 'Disetujui',
                    'verified_by_admin_id' => $adminId,
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('user_id', $doctor->user_id)
                ->update([
                    'account_status' => 'Aktif',
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('admin.web.doctors.pending')
            ->with('success', 'Dokter berhasil diverifikasi.');
    }

    public function rejectDoctor(Request $request, $doctorId)
    {
        $doctor = DB::table('doctors')
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$doctor) {
            return back()->with('error', 'Data dokter tidak ditemukan.');
        }

        $adminId = DB::table('admins')
            ->where('user_id', session('admin_id'))
            ->value('admin_id');

        DB::transaction(function () use ($doctor, $adminId) {
            DB::table('doctors')
                ->where('doctor_id', $doctor->doctor_id)
                ->update([
                    'verification_status' => 'Ditolak',
                    'verified_by_admin_id' => $adminId,
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('user_id', $doctor->user_id)
                ->update([
                    'account_status' => 'Tidak Aktif',
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('admin.web.doctors.pending')
            ->with('success', 'Dokter berhasil ditolak.');
    }

    public function resetDoctorVerification($doctorId)
    {
        $doctor = DB::table('doctors')
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$doctor) {
            return back()->with('error', 'Data dokter tidak ditemukan.');
        }

        DB::transaction(function () use ($doctor) {
            DB::table('doctors')
                ->where('doctor_id', $doctor->doctor_id)
                ->update([
                    'verification_status' => 'Menunggu',
                    'verified_by_admin_id' => null,
                    'verified_at' => null,
                    'updated_at' => now(),
                ]);

            DB::table('users')
            ->where('user_id', $doctor->user_id)
            ->update([
                'account_status' => 'Tidak Aktif',
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.web.users.index')
            ->with('success', 'Pengajuan dokter berhasil direset ke status menunggu verifikasi.');
    }

    public function users(Request $request)
    {
        $query = DB::table('users as u')
            ->leftJoin('roles as r', 'u.role_id', '=', 'r.role_id')
            ->leftJoin('doctors as d', 'u.user_id', '=', 'd.user_id')
            ->select(
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'u.account_status',
                'u.email_verified_at',
                'r.role_name',
                'd.doctor_id',
                'd.verification_status as doctor_verification_status',
                'u.created_at'
            );

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('u.full_name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('u.email', 'ILIKE', "%{$keyword}%")
                    ->orWhere('u.phone_number', 'ILIKE', "%{$keyword}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('u.role_id', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('u.account_status', $request->status);
        }

        $users = $query
            ->orderByDesc('u.created_at')
            ->paginate(12)
            ->withQueryString();


        $users->withPath(route('admin.web.users.index'));

        $roles = DB::table('roles')
            ->orderBy('role_id')
            ->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function updateUserStatus(Request $request, $userId)
{
    $user = DB::table('users as u')
        ->leftJoin('roles as r', 'u.role_id', '=', 'r.role_id')
        ->where('u.user_id', $userId)
        ->select(
            'u.user_id',
            'r.role_name'
        )
        ->first();


    if (!$user) {
        return back()->with(
            'error',
            'Pengguna tidak ditemukan.'
        );
    }


    // Dokter tidak boleh mengubah status dari halaman user
    // karena harus melalui proses verifikasi dokter
    if ($user->role_name === 'Dokter') {

        return back()->with(
            'error',
            'Status dokter harus melalui proses verifikasi dokter.'
        );

    }



    $request->validate([
        'account_status' => [
            'required',
            Rule::in([
                'Aktif',
                'Tidak Aktif',
                'Terkunci'
            ])
        ],
    ]);



    DB::table('users')
        ->where('user_id', $userId)
        ->update([
            'account_status' => $request->account_status,
            'updated_at' => now(),
        ]);



    return redirect()
        ->route('admin.web.users.index')
        ->with(
            'success',
            'Status pengguna berhasil diperbarui.'
        );
}

    public function sendUserResetPasswordLink($userId)
    {
        $user = DB::table('users')
            ->where('user_id', $userId)
            ->first();

        if (!$user) {
            return back()->with('error', 'Pengguna tidak ditemukan.');
        }

        if (!$user->email) {
            return back()->with('error', 'Pengguna tidak memiliki email.');
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = url('/reset-password?token=' . $token . '&email=' . urlencode($user->email));

        Mail::send('emails.reset-password', [
            'name' => $user->full_name,
            'resetUrl' => $resetUrl,
        ], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Reset Password Akun diabetAku');
        });

        return back()->with('success', 'Link reset password berhasil dikirim ke email pengguna.');
    }
}
