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
                'message' => 'Profil keluarga tidak ditemukan'
            ], 404);
        }

        $totalPatients = DB::table('caregiver_patient_relations')
            ->where('caregiver_id', $caregiverId)
            ->where('status', 'Diterima')
            ->count();

        $totalMedicationChecklists = DB::table('medication_consumption_logs as mcl')
            ->where('mcl.input_by_user_id', $profile->user_id)
            ->count();

        $data = (array) $profile;
        $data['total_patients'] = $totalPatients;
        $data['total_medication_checklists'] = $totalMedicationChecklists;

        return response()->json([
            'message' => 'Profil keluarga berhasil diambil',
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
                'message' => 'Profil keluarga tidak ditemukan'
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
            'message' => 'Profil keluarga berhasil diperbarui'
        ]);
    }
}
