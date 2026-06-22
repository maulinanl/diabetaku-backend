<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'blood_type_name' => 'Golongan Darah',
            ],
        ],
        'rhesus-types' => [
            'title' => 'Rhesus',
            'table' => 'rhesus_types',
            'primary_key' => 'rhesus_type_id',
            'fields' => [
                'rhesus_type_name' => 'Jenis Rhesus',
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
                'unit' => 'Satuan',
            ],
        ],
        'medications' => [
            'title' => 'Data Obat',
            'table' => 'medications',
            'primary_key' => 'medication_id',
            'fields' => [
                'medication_name' => 'Nama Obat',
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
                    'account_status' => 'Nonaktif',
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

        return view('admin.users.index', compact('users'));
    }

    public function updateUserStatus(Request $request, $userId)
    {
        $request->validate([
            'account_status' => 'required|in:Aktif,Nonaktif,Diblokir',
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

        $rules = [];
        foreach ($config['fields'] as $field => $label) {
            $rules[$field] = 'nullable|string|max:255';
        }

        $request->validate($rules);

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

        $rules = [];
        foreach ($config['fields'] as $field => $label) {
            $rules[$field] = 'nullable|string|max:255';
        }

        $request->validate($rules);

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
}
