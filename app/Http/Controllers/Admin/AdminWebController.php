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
    private array $masterConfigs = [
        'specializations' => [
            'title' => 'Spesialisasi Dokter',
            'table' => 'specializations',
            'primary_key' => 'specialization_id',
            'fields' => [
                'specialization_name' => 'Nama Spesialisasi',
            ],
        ],
        'activity-types' => [
            'title' => 'Jenis Aktivitas',
            'table' => 'activity_types',
            'primary_key' => 'activity_type_id',
            'fields' => [
                'activity_name' => 'Nama Aktivitas',
            ],
        ],
        'meal-types' => [
            'title' => 'Jenis Makan',
            'table' => 'meal_types',
            'primary_key' => 'meal_type_id',
            'fields' => [
                'meal_type_name' => 'Nama Jenis Makan',
            ],
        ],
        'blood-types' => [
            'title' => 'Golongan Darah',
            'table' => 'blood_types',
            'primary_key' => 'blood_type_id',
            'fields' => [
                'blood_type' => 'Golongan Darah',
            ],
        ],
        'rhesus-types' => [
            'title' => 'Rhesus',
            'table' => 'rhesus_types',
            'primary_key' => 'rhesus_type_id',
            'fields' => [
                'rhesus_type' => 'Jenis Rhesus',
            ],
        ],
        'relation-types' => [
            'title' => 'Tipe Relasi Keluarga',
            'table' => 'relation_types',
            'primary_key' => 'relation_type_id',
            'fields' => [
                'relation_name' => 'Nama Relasi',
            ],
        ],
        'clinical-parameters' => [
            'title' => 'Parameter Klinis',
            'table' => 'clinical_parameters',
            'primary_key' => 'parameter_id',
            'fields' => [
                'parameter_name' => 'Nama Parameter',
                'default_min' => 'Nilai Minimum',
                'default_max' => 'Nilai Maksimum',
                'valid_min' => 'Rentang Valid Minimum',
                'valid_max' => 'Rentang Valid Maksimum',
                'unit' => 'Satuan',
            ],
        ],
        'medications' => [
            'title' => 'Data Obat',
            'table' => 'medications',
            'primary_key' => 'medication_id',
            'fields' => [
                'medication_name' => 'Nama Obat',
                'dosage_form' => 'Bentuk Sediaan',
                'value' => 'Nilai Dosis',
                'unit' => 'Satuan',
                'description' => 'Deskripsi',
            ],
        ],
        'medication-sessions' => [
            'title' => 'Sesi Minum Obat',
            'table' => 'medication_sessions',
            'primary_key' => 'session_id',
            'fields' => [
                'session_name' => 'Nama Sesi',
                'start_time' => 'Jam Mulai',
                'end_time' => 'Jam Selesai',
                'default_reminder_time' => 'Jam Pengingat Default',
            ],
        ],
        'notification-types' => [
            'title' => 'Tipe Notifikasi',
            'table' => 'notification_types',
            'primary_key' => 'notification_type_id',
            'fields' => [
                'notification_type_name' => 'Nama Tipe Notifikasi',
            ],
        ],
    ];

    public function dashboard()
    {
        $totalUsers = DB::table('users')->count();
        $totalPatients = DB::table('patients')->count();
        $totalDoctors = DB::table('doctors')->count();

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

        DB::transaction(function () use ($doctor) {
            DB::table('doctors')
                ->where('doctor_id', $doctor->doctor_id)
                ->update([
                    'verification_status' => 'Disetujui',
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

    public function rejectDoctor($doctorId)
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
                    'verification_status' => 'Ditolak',
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

    public function users()
    {
        $users = DB::table('users as u')
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
                'd.verification_status as doctor_verification_status',
                'u.created_at'
            )
            ->orderByDesc('u.created_at')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function updateUserStatus(Request $request, $userId)
    {
        $request->validate([
            'account_status' => ['required', Rule::in(['Menunggu Verifikasi', 'Aktif', 'Tidak Aktif', 'Terkunci'])],
        ]);

        DB::table('users')
            ->where('user_id', $userId)
            ->update([
                'account_status' => $request->account_status,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.web.users.index')
            ->with('success', 'Status pengguna berhasil diperbarui.');
    }

    private function masterDataRules(string $type, array $config): array
    {
        if ($type === 'clinical-parameters') {
            return [
                'parameter_name' => 'required|string|max:255',
                'default_min' => 'required|numeric',
                'default_max' => 'required|numeric|gt:default_min',
                'valid_min' => 'required|numeric|lte:default_min',
                'valid_max' => 'required|numeric|gte:default_max|gt:valid_min',
                'unit' => 'required|string|max:50',
            ];
        }

        if ($type === 'medications') {
            return [
                'medication_name' => 'required|string|max:100',
                'dosage_form' => ['nullable', Rule::in(['Tablet', 'Kapsul', 'Sirup', 'Injeksi', 'Tetes', 'Krim/Salep'])],
                'value' => 'nullable|numeric',
                'unit' => 'nullable|string|max:20',
                'description' => 'nullable|string',
            ];
        }

        if ($type === 'medication-sessions') {
            return [
                'session_name' => 'required|string|max:100',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'default_reminder_time' => 'required|date_format:H:i',
            ];
        }

        $rules = [];
        foreach ($config['fields'] as $field => $label) {
            $rules[$field] = 'required|string|max:255';
        }

        return $rules;
    }

    public function masterData($type = 'specializations')
    {
        if (!array_key_exists($type, $this->masterConfigs)) {
            abort(404);
        }

        $config = $this->masterConfigs[$type];

        $items = DB::table($config['table'])
            ->orderBy($config['primary_key'])
            ->get();

        $masterMenus = $this->masterConfigs;

        return view('admin.master.index', compact(
            'type',
            'config',
            'items',
            'masterMenus'
        ));
    }

    public function storeMasterData(Request $request, $type)
    {
        if (!array_key_exists($type, $this->masterConfigs)) {
            abort(404);
        }

        $config = $this->masterConfigs[$type];

        $request->validate($this->masterDataRules($type, $config));

        $payload = [];
        foreach ($config['fields'] as $field => $label) {
            $payload[$field] = $request->input($field);
        }

        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table($config['table'])->insert($payload);

        return redirect()
            ->route('admin.web.master.index', $type)
            ->with('success', 'Data master berhasil ditambahkan.');
    }

    public function updateMasterData(Request $request, $type, $id)
    {
        if (!array_key_exists($type, $this->masterConfigs)) {
            abort(404);
        }

        $config = $this->masterConfigs[$type];

        $request->validate($this->masterDataRules($type, $config));

        $payload = [];
        foreach ($config['fields'] as $field => $label) {
            $payload[$field] = $request->input($field);
        }

        $payload['updated_at'] = now();

        DB::table($config['table'])
            ->where($config['primary_key'], $id)
            ->update($payload);

        return redirect()
            ->route('admin.web.master.index', $type)
            ->with('success', 'Data master berhasil diperbarui.');
    }

    public function deleteMasterData($type, $id)
    {
        if (!array_key_exists($type, $this->masterConfigs)) {
            abort(404);
        }

        $config = $this->masterConfigs[$type];

        try {
            DB::table($config['table'])
                ->where($config['primary_key'], $id)
                ->delete();

            return redirect()
                ->route('admin.web.master.index', $type)
                ->with('success', 'Data master berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.web.master.index', $type)
                ->with('error', 'Data tidak bisa dihapus karena masih digunakan pada data lain.');
        }
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
