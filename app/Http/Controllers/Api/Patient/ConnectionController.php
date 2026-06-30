<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PrescriptionLifecycleService;

class ConnectionController extends Controller
{
    private function getNotificationTypeId($typeName)
    {
        return DB::table('notification_types')
            ->where('notification_type_name', $typeName)
            ->value('notification_type_id');
    }

    private function createNotification($userId, $typeName, $title, $message, $referenceId = null, $referenceType = null)
    {
        if (!$userId || !$typeName) return;

        $typeId = $this->getNotificationTypeId($typeName);
        if (!$typeId) return;

        $notificationId = DB::table('notifications')->insertGetId([
            'user_id' => $userId,
            'notification_type_id' => $typeId,
            'title' => $title,
            'message' => $message,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'notification_id');

        $sendPushNotification = function () use (
            $userId,
            $title,
            $message,
            $notificationId,
            $referenceId,
            $referenceType,
            $typeId
        ) {
            try {
                app(\App\Services\FcmService::class)->sendToUser(
                    $userId,
                    $title,
                    $message,
                    [
                        'notification_id' => $notificationId,
                        'reference_id' => $referenceId ?? '',
                        'reference_type' => $referenceType ?? '',
                        'notification_type_id' => $typeId,
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($sendPushNotification);
        } else {
            $sendPushNotification();
        }
    }

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
        $families = DB::table('caregiver_patient_relations as fpr')
            ->join('caregivers as f', 'fpr.caregiver_id', '=', 'f.caregiver_id')
            ->join('users as u', 'f.user_id', '=', 'u.user_id')
            ->join('relation_types as rt', 'fpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('fpr.patient_id', $patientId)
            ->where('fpr.status', 'Diterima')
            ->select(
                'fpr.caregiver_id as family_id',
                'fpr.caregiver_id',
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
        $requests = DB::table('caregiver_patient_relations as fpr')
            ->join('caregivers as f', 'fpr.caregiver_id', '=', 'f.caregiver_id')
            ->join('users as u', 'f.user_id', '=', 'u.user_id')
            ->join('relation_types as rt', 'fpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('fpr.patient_id', $patientId)
            ->where('fpr.status', 'Menunggu')
            ->select(
                'fpr.caregiver_id as family_id',
                'fpr.caregiver_id',
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
                'dpr.status as relation_status',
                'dpr.connected_at as connected_since',
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

        DB::transaction(function () use ($request, $doctorId) {
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
                    'disconnected_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $doctorUserId = DB::table('doctors')
                ->where('doctor_id', $doctorId)
                ->value('user_id');

            $patientName = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $request->patient_id)
                ->value('u.full_name');

            $this->createNotification(
                $doctorUserId,
                'Permintaan Koneksi',
                'Permintaan koneksi baru',
                ($patientName ?? 'Pasien') . ' mengajukan permintaan untuk terhubung dengan dokter.',
                $request->patient_id,
                'doctor_connection_request'
            );
        });

        return response()->json([
            'message' => 'Permintaan koneksi dokter berhasil dikirim'
        ], 201);
    }

    public function disconnectDoctor(Request $request, $doctorId)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        return DB::transaction(function () use ($request, $doctorId) {
            $updated = DB::table('doctor_patient_relations')
                ->where('patient_id', $request->patient_id)
                ->where('doctor_id', $doctorId)
                ->where('status', 'Diterima')
                ->update([
                    'status' => 'Diputus',
                    'disconnected_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return response()->json([
                    'message' => 'Relasi dokter tidak ditemukan atau sudah tidak aktif'
                ], 404);
            }

            app(PrescriptionLifecycleService::class)
                ->finishActivePrescriptionsForRelation(
                    (int) $doctorId,
                    (int) $request->patient_id
                );

            $doctorUserId = DB::table('doctors')
                ->where('doctor_id', $doctorId)
                ->value('user_id');

            $patientName = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $request->patient_id)
                ->value('u.full_name');

            $this->createNotification(
                $doctorUserId,
                'Putus Relasi',
                'Relasi pasien terputus',
                'Relasi dengan ' . ($patientName ?? 'pasien') . ' telah diputus. Data lama masih dapat dilihat.',
                $request->patient_id,
                'doctor_patient_disconnected'
            );

            return response()->json([
                'message' => 'Relasi dokter berhasil diputus'
            ]);
        });
    }

    public function acceptFamilyRequest(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'family_id' => 'required|exists:caregivers,caregiver_id',
        ]);

        DB::transaction(function () use ($request) {
            $updated = DB::table('caregiver_patient_relations')
                ->where('patient_id', $request->patient_id)
                ->where('caregiver_id', $request->family_id)
                ->where('status', 'Menunggu')
                ->update([
                    'status' => 'Diterima',
                    'responded_at' => now(),
                    'connected_at' => now(),
                    'disconnected_at' => null,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw new \Exception('Permintaan tidak ditemukan atau sudah diproses');
            }

            $familyUserId = DB::table('caregivers')
                ->where('caregiver_id', $request->family_id)
                ->value('user_id');

            $patientName = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $request->patient_id)
                ->value('u.full_name');

            $this->createNotification(
                $familyUserId,
                'Permintaan Koneksi',
                'Permintaan koneksi diterima',
                ($patientName ?? 'Pasien') . ' telah menerima permintaan koneksi Anda.',
                $request->patient_id,
                'family_connection_accepted'
            );
        });

        return response()->json([
            'message' => 'Permintaan keluarga berhasil diterima'
        ]);
    }

    public function rejectFamilyRequest(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'family_id' => 'required|exists:caregivers,caregiver_id',
        ]);

        DB::transaction(function () use ($request) {
            DB::table('caregiver_patient_relations')
                ->where('patient_id', $request->patient_id)
                ->where('caregiver_id', $request->family_id)
                ->where('status', 'Menunggu')
                ->update([
                    'status' => 'Ditolak',
                    'responded_at' => now(),
                    'updated_at' => now(),
                ]);

            $familyUserId = DB::table('caregivers')
                ->where('caregiver_id', $request->family_id)
                ->value('user_id');

            $patientName = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $request->patient_id)
                ->value('u.full_name');

            $this->createNotification(
                $familyUserId,
                'Permintaan Koneksi',
                'Permintaan koneksi ditolak',
                ($patientName ?? 'Pasien') . ' menolak permintaan koneksi Anda.',
                $request->patient_id,
                'family_connection_rejected'
            );
        });

        return response()->json([
            'message' => 'Permintaan koneksi ditolak'
        ]);
    }

    public function disconnectFamily(Request $request, $familyId)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        return DB::transaction(function () use ($request, $familyId) {
            DB::table('caregiver_patient_relations')
                ->where('patient_id', $request->patient_id)
                ->where('caregiver_id', $familyId)
                ->where('status', 'Diterima')
                ->update([
                    'status' => 'Diputus',
                    'disconnected_at' => now(),
                    'updated_at' => now(),
                ]);

            $familyUserId = DB::table('caregivers')
                ->where('caregiver_id', $familyId)
                ->value('user_id');

            $patientName = DB::table('patients as p')
                ->join('users as u', 'p.user_id', '=', 'u.user_id')
                ->where('p.patient_id', $request->patient_id)
                ->value('u.full_name');

            $this->createNotification(
                $familyUserId,
                'Putus Relasi',
                'Relasi pasien terputus',
                'Relasi dengan ' . ($patientName ?? 'pasien') . ' telah diputus.',
                $request->patient_id,
                'family_connection_disconnected'
            );

            return response()->json([
                'message' => 'Relasi keluarga berhasil diputus'
            ]);
        });
    }
}
