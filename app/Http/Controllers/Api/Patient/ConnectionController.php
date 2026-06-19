<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConnectionController extends Controller
{
    public function connectedDoctors($patientId)
    {
        $doctors = DB::table('doctor_patient_relations as dpr')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->where('dpr.patient_id', $patientId)
            ->where('dpr.status', 'Diterima')
            ->select(
                'dpr.doctor_id',
                'u.full_name as doctor_name',
                'u.email',
                'u.phone_number',
                's.specialization_name',
                'd.institution',
                'dpr.connected_at as connected_since'
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Dokter terhubung berhasil diambil',
            'data' => $doctors
        ]);
    }

    public function connectedFamilies($patientId)
    {
        $families = DB::table('family_patient_relations as fpr')
            ->join('families as f', 'fpr.family_id', '=', 'f.family_id')
            ->join('users as u', 'f.user_id', '=', 'u.user_id')
            ->join('relation_types as rt', 'fpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('fpr.patient_id', $patientId)
            ->where('fpr.status', 'Diterima')
            ->select(
                'fpr.family_id',
                'u.full_name as family_name',
                'u.phone_number',
                'rt.relation_name',
                'fpr.status',
                'fpr.connected_at'
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Keluarga terhubung berhasil diambil',
            'data' => $families
        ]);
    }

    public function incomingRequests($patientId)
    {
        $requests = DB::table('family_patient_relations as fpr')
            ->join('families as f', 'fpr.family_id', '=', 'f.family_id')
            ->join('users as u', 'f.user_id', '=', 'u.user_id')
            ->join('relation_types as rt', 'fpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('fpr.patient_id', $patientId)
            ->where('fpr.status', 'Menunggu')
            ->select(
                'fpr.family_id',
                'u.full_name as family_name',
                'rt.relation_name',
                'fpr.status',
                'fpr.requested_at'
            )
            ->orderByDesc('fpr.requested_at')
            ->get();

        return response()->json([
            'message' => 'Permintaan koneksi berhasil diambil',
            'data' => $requests
        ]);
    }

    public function searchDoctors(Request $request)
    {
        $keyword = $request->keyword;
        $patientId = $request->patient_id;

        $doctors = DB::table('doctors as d')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->leftJoin('doctor_patient_relations as dpr', function ($join) use ($patientId) {
                $join->on('d.doctor_id', '=', 'dpr.doctor_id')
                    ->where('dpr.patient_id', '=', $patientId);
            })
            ->where('d.verification_status', 'Disetujui')
            ->where('u.account_status', 'Aktif')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('u.full_name', 'ILIKE', "%$keyword%")
                        ->orWhere('s.specialization_name', 'ILIKE', "%$keyword%")
                        ->orWhere('d.institution', 'ILIKE', "%$keyword%");
                });
            })
            ->select(
                'd.doctor_id',
                'u.full_name',
                's.specialization_name',
                'd.institution',
                DB::raw("
                    CASE
                        WHEN dpr.status = 'Diterima' THEN 'Terhubung'
                        WHEN dpr.status = 'Menunggu' THEN 'Menunggu'
                        ELSE 'Belum Terhubung'
                    END as connection_status
                ")
            )
            ->orderBy('u.full_name')
            ->get();

        return response()->json([
            'message' => 'Data dokter ditemukan',
            'data' => $doctors
        ]);
    }

    public function requestDoctorConnection(Request $request, $doctorId)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        DB::table('doctor_patient_relations')->updateOrInsert(
        [
            'patient_id' => $request->patient_id,
            'doctor_id' => $doctorId,
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
            'message' => 'Permintaan koneksi dokter berhasil dikirim'
        ], 201);
    }

    public function disconnectDoctor(Request $request, $doctorId)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        $updated = DB::table('doctor_patient_relations')
            ->where('patient_id', $request->patient_id)
            ->where('doctor_id', $doctorId)
            ->where('status', 'Diterima')
            ->update([
                'status' => 'Diputus',
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json([
                'message' => 'Relasi dokter tidak ditemukan atau sudah tidak aktif'
            ], 404);
        }

        return response()->json([
            'message' => 'Relasi dokter berhasil diputus'
        ]);
    }

    public function acceptFamilyRequest(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'family_id' => 'required|exists:families,family_id',
        ]);

        DB::transaction(function () use ($request) {
            DB::table('family_patient_relations')
                ->where('patient_id', $request->patient_id)
                ->where('family_id', $request->family_id)
                ->update([
                    'status' => 'Diterima',
                    'responded_at' => now(),
                    'connected_at' => now(),
                    'updated_at' => now(),
                ]);

            $family = DB::table('families')
                ->join('users', 'families.user_id', '=', 'users.user_id')
                ->where('families.family_id', $request->family_id)
                ->select('families.family_id', 'users.user_id', 'users.full_name')
                ->first();

            $patient = DB::table('patients')
                ->join('users', 'patients.user_id', '=', 'users.user_id')
                ->where('patients.patient_id', $request->patient_id)
                ->select('patients.patient_id', 'users.full_name')
                ->first();

            $typeId = DB::table('notification_types')
                ->where('notification_type_name', 'Koneksi')
                ->value('notification_type_id');

            if (!$typeId) {
                $typeId = DB::table('notification_types')->insertGetId([
                    'notification_type_name' => 'Koneksi',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'notification_type_id');
            }

            if ($family && $patient) {
                DB::table('notifications')->insert([
                    'user_id' => $family->user_id,
                    'notification_type_id' => $typeId,
                    'title' => 'Permintaan koneksi diterima',
                    'message' => $patient->full_name . ' menyetujui permintaan koneksi Anda sebagai pendamping.',
                    'reference_id' => $request->patient_id,
                    'reference_type' => 'family_connection',
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Permintaan keluarga berhasil diterima'
        ]);
    }

    public function rejectFamilyRequest(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'family_id' => 'required|exists:families,family_id',
        ]);

        DB::table('family_patient_relations')
            ->where('patient_id', $request->patient_id)
            ->where('family_id', $request->family_id)
            ->where('status', 'Menunggu')
            ->update([
                'status' => 'Ditolak',
                'responded_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Permintaan berhasil ditolak'
        ]);
    }

    public function disconnectFamily(Request $request, $familyId)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        DB::table('family_patient_relations')
            ->where('patient_id', $request->patient_id)
            ->where('family_id', $familyId)
            ->where('status', 'Diterima')
            ->update([
                'status' => 'Diputus',
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Relasi keluarga berhasil diputus'
        ]);
    }
}
