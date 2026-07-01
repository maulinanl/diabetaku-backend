<?php

namespace App\Http\Controllers\Api\Caregiver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show($caregiverId)
    {
        $profile = DB::table('caregivers as f')
            ->join('users as u', 'f.user_id', '=', 'u.user_id')
            ->where('f.caregiver_id', $caregiverId)
            ->select(
                'f.caregiver_id as caregiver_id',
                'f.caregiver_id',
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                DB::raw('NULL as date_of_birth'),
                'u.gender',
                'u.email_verified_at',
                'u.account_status'
            )
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profil pendamping tidak ditemukan'
            ], 404);
        }

        $totalPatients = DB::table('caregiver_patient_relations')
            ->where('caregiver_id', $caregiverId)
            ->where('status', 'Diterima')
            ->count();

        $today = now()->toDateString();

        // Jumlah jadwal minum obat hari ini dari seluruh pasien dampingan.
        // 1 resep dengan 3 sesi minum dihitung sebagai 3 jadwal, karena yang
        // dipantau pendamping adalah frekuensi pengingat minum obat harian.
        $totalMedicationSchedulesToday = DB::table('caregiver_patient_relations as cpr')
            ->join('doctor_patient_relations as dpr', 'dpr.patient_id', '=', 'cpr.patient_id')
            ->join('prescriptions as p', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('prescription_schedules as ps', 'ps.prescription_id', '=', 'p.prescription_id')
            ->where('cpr.caregiver_id', $caregiverId)
            ->where('cpr.status', 'Diterima')
            ->where('p.status_prescription', 'Aktif')
            ->where(function ($query) use ($today) {
                $query->whereNull('p.start_date')
                    ->orWhere('p.start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('p.end_date')
                    ->orWhere('p.end_date', '>=', $today);
            })
            ->count('ps.prescription_schedule_id');

        $data = (array) $profile;
        $data['total_patients'] = $totalPatients;
        $data['total_medication_schedules_today'] = $totalMedicationSchedulesToday;
        // Backward-compatible key for older Flutter builds.
        $data['total_medication_checklists'] = $totalMedicationSchedulesToday;

        return response()->json([
            'message' => 'Profil pendamping berhasil diambil',
            'data' => $data
        ]);
    }

    public function update(Request $request, $caregiverId)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
        ]);

        $caregiver = DB::table('caregivers')
            ->where('caregiver_id', $caregiverId)
            ->first();

        if (!$caregiver) {
            return response()->json([
                'message' => 'Profil pendamping tidak ditemukan'
            ], 404);
        }

        DB::table('users')
            ->where('user_id', $caregiver->user_id)
            ->update([
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Profil pendamping berhasil diperbarui'
        ]);
    }
}
