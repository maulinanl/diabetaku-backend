<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function sendPushNotification(
        $userId,
        $title,
        $message,
        $notificationId = null,
        $referenceId = null,
        $referenceType = null,
        $notificationTypeId = null
    ) {
        $send = function () use (
            $userId,
            $title,
            $message,
            $notificationId,
            $referenceId,
            $referenceType,
            $notificationTypeId
        ) {
            try {
                app(FcmService::class)->sendToUser(
                    $userId,
                    $title,
                    $message,
                    [
                        'notification_id' => $notificationId ?? '',
                        'reference_id' => $referenceId ?? '',
                        'reference_type' => $referenceType ?? '',
                        'notification_type_id' => $notificationTypeId ?? '',
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($send);
        } else {
            $send();
        }
    }


    private function initialsFromName(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '-';
        }

        $parts = preg_split('/\s+/', $name);

        if (count($parts) === 1) {
            return strtoupper(mb_substr($parts[0], 0, 1));
        }

        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }

    private function normalizeReferenceType(?string $value): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim((string) $value)));
    }

    private function statusFromReferenceType(string $referenceType, ?string $title = null, ?string $message = null): string
    {
        $text = strtolower($referenceType . ' ' . (string) $title . ' ' . (string) $message);

        if (str_contains($text, 'rejected') || str_contains($text, 'ditolak') || str_contains($text, 'menolak')) {
            return 'Ditolak';
        }

        if (str_contains($text, 'disconnected') || str_contains($text, 'diputus') || str_contains($text, 'terputus')) {
            return 'Tidak Terhubung';
        }

        if (str_contains($text, 'accepted') || str_contains($text, 'diterima') || str_contains($text, 'terhubung')) {
            return 'Terhubung';
        }

        if (str_contains($text, 'request') || str_contains($text, 'menunggu')) {
            return 'Menunggu';
        }

        return 'Terhubung';
    }

    private function normalizeDoctorRelationStatus(?string $status): ?string
    {
        $normalized = strtolower(trim((string) $status));

        if ($normalized === '') {
            return null;
        }

        if ($normalized === 'diterima' || $normalized === 'disetujui' || $normalized === 'terhubung') {
            return 'Terhubung';
        }

        if ($normalized === 'menunggu') {
            return 'Menunggu';
        }

        if ($normalized === 'ditolak') {
            return 'Ditolak';
        }

        if ($normalized === 'diputus' || $normalized === 'tidak terhubung' || $normalized === 'terputus') {
            return 'Tidak Terhubung';
        }

        return $status;
    }

    private function attachDoctorDetail($data, int $doctorId): void
    {
        $doctor = DB::table('doctors as d')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->where('d.doctor_id', $doctorId)
            ->select(
                'd.doctor_id',
                'u.full_name as doctor_name',
                's.specialization_name',
                'd.institution'
            )
            ->first();

        if (!$doctor) {
            return;
        }

        $data->doctor_id = $doctor->doctor_id;
        $data->doctor_name = $doctor->doctor_name;
        $data->initial = $this->initialsFromName($doctor->doctor_name);
        $data->specialization_name = $doctor->specialization_name ?? '-';
        $data->institution = $doctor->institution ?? '-';
        $data->info = trim(($doctor->specialization_name ?? '-') . ' • ' . ($doctor->institution ?? '-'));

        // Untuk notifikasi lama seperti "Koneksi Dokter Diterima", status yang dipakai
        // untuk tombol detail dokter harus mengikuti relasi TERBARU di database.
        // Jadi kalau relasi sudah Diputus, halaman detail dokter tidak menampilkan tombol Putus Relasi lagi.
        $patientId = DB::table('patients')
            ->where('user_id', $data->user_id)
            ->value('patient_id');

        if (!$patientId) {
            return;
        }

        $relation = DB::table('doctor_patient_relations')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->orderByDesc('updated_at')
            ->first();

        if (!$relation) {
            $data->current_relation_status = 'Belum Terhubung';
            $data->status = 'Belum Terhubung';
            return;
        }

        $currentStatus = $this->normalizeDoctorRelationStatus($relation->status ?? null);

        $data->doctor_patient_relation_id = $relation->doctor_patient_relation_id ?? null;
        $data->current_relation_status = $currentStatus ?? 'Belum Terhubung';
        $data->relation_status = $currentStatus ?? 'Belum Terhubung';
        $data->status = $currentStatus ?? 'Belum Terhubung';
        $data->connected_since = $relation->connected_at ?? null;
        $data->relation_updated_at = $relation->updated_at ?? null;
    }

    private function attachPatientDetail($data, int $patientId): void
    {
        $patient = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.patient_id', $patientId)
            ->select(
                'p.patient_id',
                'u.full_name as patient_name',
                'u.gender',
                'p.date_of_birth',
                'p.diabetes_type'
            )
            ->first();

        if (!$patient) {
            return;
        }

        $data->patient_id = $patient->patient_id;
        $data->patient_name = $patient->patient_name;
        $data->full_name = $patient->patient_name;
        $data->initial = $this->initialsFromName($patient->patient_name);
        $data->gender = $patient->gender;
        $data->date_of_birth = $patient->date_of_birth;
        $data->diabetes_type = $patient->diabetes_type;
    }

    private function attachFamilyDetail($data, int $familyId): void
    {
        // Database terbaru memakai caregivers, tetapi response tetap mengirim alias family_id
        // agar frontend lama tidak perlu diganti sekaligus.
        $family = DB::table('caregivers as c')
            ->join('users as u', 'c.user_id', '=', 'u.user_id')
            ->leftJoin('caregiver_patient_relations as cpr', function ($join) use ($data) {
                $join->on('c.caregiver_id', '=', 'cpr.caregiver_id');

                $patientId = DB::table('patients')
                    ->where('user_id', $data->user_id)
                    ->value('patient_id');

                if ($patientId) {
                    $join->where('cpr.patient_id', '=', $patientId);
                }
            })
            ->leftJoin('relation_types as rt', 'cpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('c.caregiver_id', $familyId)
            ->select(
                'c.caregiver_id',
                DB::raw('c.caregiver_id as family_id'),
                'u.full_name as family_name',
                'rt.relation_name',
                'cpr.status',
                'cpr.requested_at',
                'cpr.responded_at',
                'cpr.connected_at',
                'cpr.disconnected_at',
                'cpr.updated_at as relation_updated_at'
            )
            ->orderByDesc('cpr.updated_at')
            ->orderByDesc('cpr.requested_at')
            ->first();

        if (!$family) {
            return;
        }

        $data->family_id = $family->family_id;
        $data->caregiver_id = $family->caregiver_id;
        $data->family_name = $family->family_name;
        $data->full_name = $family->family_name;
        $data->name = $family->family_name;
        $data->initial = $this->initialsFromName($family->family_name);
        $data->relation = $family->relation_name ?? 'Keluarga';
        $data->relation_name = $family->relation_name ?? 'Keluarga';
        $data->status = $family->status ?? 'Menunggu';
        $data->requested_at = $family->requested_at ?? $data->created_at;
        $data->responded_at = $family->responded_at;
        $data->connected_at = $family->connected_at;
        $data->disconnected_at = $family->disconnected_at;
        $data->relation_updated_at = $family->relation_updated_at;
    }

    private function attachPrescriptionDetail($data): void
    {
        $prescriptionId = (int) $data->reference_id;

        $prescription = DB::table('prescriptions as p')
            ->leftJoin('doctor_patient_relations as dpr', 'p.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->leftJoin('medications as m', 'p.medication_id', '=', 'm.medication_id')
            ->leftJoin('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->leftJoin('users as du', 'd.user_id', '=', 'du.user_id')
            ->where('p.prescription_id', $prescriptionId)
            ->select(
                'p.prescription_id',
                'dpr.patient_id',
                'dpr.doctor_id',
                'p.doctor_patient_relation_id',
                'p.medication_id',
                DB::raw("TRIM(COALESCE(p.quantity::text, '') || ' ' || COALESCE(p.quantity_unit, '')) as dosage"),
                'm.dosage_form as form',
                DB::raw('NULL::text as indication'),
                'p.meal_rule',
                'p.notes',
                'p.status_prescription as status',
                'p.start_date as valid_from',
                'p.end_date as valid_until',
                'p.replaced_by',
                'p.created_at as prescription_created_at',
                'p.updated_at as prescription_updated_at',
                'm.medication_name',
                'm.description as medication_description',
                'du.full_name as doctor_name'
            )
            ->first();

        if (!$prescription) {
            return;
        }

        foreach ((array) $prescription as $key => $value) {
            $data->{$key} = $value;
        }

        $data->initial = $this->initialsFromName($prescription->doctor_name ?? 'Dokter');

        $data->schedules = DB::table('prescription_schedules as ps')
            ->leftJoin('medication_sessions as ms', 'ps.session_id', '=', 'ms.session_id')
            ->where('ps.prescription_id', $prescriptionId)
            ->select(
                'ps.prescription_schedule_id as schedule_id',
                'ps.prescription_schedule_id',
                'ps.session_id',
                'ms.session_name',
                'ms.default_reminder_time',
                DB::raw('ms.default_reminder_time as reminder_time'),
                DB::raw("TRIM(COALESCE((SELECT quantity::text FROM prescriptions WHERE prescriptions.prescription_id = ps.prescription_id), '') || ' ' || COALESCE((SELECT quantity_unit FROM prescriptions WHERE prescriptions.prescription_id = ps.prescription_id), '')) as dose_per_session"),
                DB::raw('true as is_active')
            )
            ->orderBy('ms.start_time')
            ->get();
    }

    private function attachRecommendationDetail($data, string $referenceType): void
    {
        $referenceId = (int) $data->reference_id;

        $recommendationsQuery = DB::table('recommendations as r')
            ->join('clinical_notes as cn', 'r.clinical_note_id', '=', 'cn.clinical_note_id')
            ->join('doctor_patient_relations as dpr', 'cn.doctor_patient_relation_id', '=', 'dpr.doctor_patient_relation_id')
            ->join('doctors as d', 'dpr.doctor_id', '=', 'd.doctor_id')
            ->join('users as du', 'd.user_id', '=', 'du.user_id')
            ->select(
                'r.recommendation_id',
                'r.clinical_note_id',
                'r.category',
                'r.recommendation_text',
                'r.created_at',
                'du.full_name as doctor_name'
            );

        if ($referenceType === 'recommendation') {
            $recommendationsQuery->where('r.recommendation_id', $referenceId);
        } else {
            $recommendationsQuery->where('r.clinical_note_id', $referenceId);
        }

        $recommendations = $recommendationsQuery
            ->orderBy('r.recommendation_id')
            ->get();

        if ($recommendations->isEmpty()) {
            return;
        }

        $first = $recommendations->first();

        $data->recommendation_id = $first->recommendation_id;
        $data->clinical_note_id = $first->clinical_note_id;
        $data->doctor_name = $first->doctor_name;
        $data->category = $recommendations->count() === 1 ? $first->category : $recommendations->count() . ' Rekomendasi';
        $data->recommendation_text = $first->recommendation_text;
        $data->recommendations = $recommendations;
    }

    private function enrichNotification($data)
    {
        if (!$data || !$data->reference_id || !$data->reference_type) {
            return $data;
        }

        $referenceType = $this->normalizeReferenceType($data->reference_type);
        $data->reference_type = $referenceType;

        if ($referenceType === 'doctor_connection_request') {
            $this->attachPatientDetail($data, (int) $data->reference_id);
            $data->status = 'Menunggu';
            return $data;
        }

        if (in_array($referenceType, [
            'doctor_connection_accepted',
            'doctor_connection_rejected',
            'doctor_connection_disconnected',
            'doctor_connection',
        ], true)) {
            $this->attachDoctorDetail($data, (int) $data->reference_id);

            // Kalau attachDoctorDetail tidak menemukan relasi terbaru, baru fallback dari reference_type/title/message.
            if (empty($data->status) || $data->status === '-') {
                $data->status = $this->statusFromReferenceType($referenceType, $data->title, $data->message);
            }

            return $data;
        }

        if ($referenceType === 'doctor_patient_disconnected') {
            $this->attachPatientDetail($data, (int) $data->reference_id);
            $data->status = 'Tidak Terhubung';
            return $data;
        }

        if (in_array($referenceType, [
            'family_request',
            'family_connection_request',
        ], true)) {
            $this->attachFamilyDetail($data, (int) $data->reference_id);

            // Untuk permintaan keluarga, status harus mengikuti tabel caregiver_patient_relations.
            // Jadi setelah pasien klik Terima/Tolak, detail notifikasi ikut berubah.
            if (empty($data->status) || $data->status === '-') {
                $data->status = $this->statusFromReferenceType($referenceType, $data->title, $data->message);
            }

            return $data;
        }

        if (in_array($referenceType, [
            'family_connection_disconnected',
            'family_disconnected',
            'family_patient_disconnected',
        ], true)) {
            $patientIdForUser = DB::table('patients')
                ->where('user_id', $data->user_id)
                ->value('patient_id');

            $familyIdForUser = DB::table('caregivers')
                ->where('user_id', $data->user_id)
                ->value('caregiver_id');

            // Kalau penerima notifikasi adalah pasien, reference_id berisi family_id.
            // Detail harus menampilkan data keluarga, bukan data dokter.
            if ($patientIdForUser) {
                $this->attachFamilyDetail($data, (int) $data->reference_id);
            }

            // Kalau penerima notifikasi adalah keluarga, reference_id berisi patient_id.
            // Detail harus menampilkan data pasien.
            if (!$patientIdForUser && $familyIdForUser) {
                $this->attachPatientDetail($data, (int) $data->reference_id);
            }

            $data->status = 'Tidak Terhubung';
            return $data;
        }

        if (in_array($referenceType, [
            'family_connection_accepted',
            'family_connection_rejected',
        ], true)) {
            $this->attachPatientDetail($data, (int) $data->reference_id);
            $data->status = $this->statusFromReferenceType($referenceType, $data->title, $data->message);
            return $data;
        }

        if (in_array($referenceType, [
            'prescription',
            'prescription_created',
            'prescription_updated',
            'prescription_stopped',
        ], true)) {
            $this->attachPrescriptionDetail($data);
            return $data;
        }

        if (in_array($referenceType, ['recommendation', 'clinical_note'], true)) {
            $this->attachRecommendationDetail($data, $referenceType);
            return $data;
        }

        return $data;
    }


    public function index(Request $request, $userId)
    {
        $authUser = $request->user();

        if ($authUser && (int) $authUser->user_id !== (int) $userId) {
            return response()->json([
                'message' => 'Tidak boleh mengakses notifikasi pengguna lain'
            ], 403);
        }

        $data = DB::table('notifications as n')
            ->leftJoin('notification_types as nt', 'n.notification_type_id', '=', 'nt.notification_type_id')
            ->where('n.user_id', $userId)
            ->select(
                'n.notification_id',
                'n.user_id',
                'n.notification_type_id',
                DB::raw("COALESCE(nt.notification_type_name, '-') as type"),
                DB::raw("LOWER(REPLACE(COALESCE(nt.notification_type_name, ''), ' ', '_')) as type_code"),
                'n.title',
                'n.message',
                'n.reference_id',
                'n.reference_type',
                'n.is_read',
                'n.created_at',
                'n.updated_at'
            )
            ->orderByDesc('n.created_at')
            ->get();

        return response()->json([
            'message' => 'Notifikasi berhasil diambil',
            'data' => $data
        ]);
    }

    public function show(Request $request, $notificationId)
    {
        $data = DB::table('notifications as n')
            ->leftJoin('notification_types as nt', 'n.notification_type_id', '=', 'nt.notification_type_id')
            ->where('n.notification_id', $notificationId)
            ->select(
                'n.notification_id',
                'n.user_id',
                'n.notification_type_id',
                DB::raw("COALESCE(nt.notification_type_name, '-') as type"),
                DB::raw("LOWER(REPLACE(COALESCE(nt.notification_type_name, ''), ' ', '_')) as type_code"),
                'n.title',
                'n.message',
                'n.reference_id',
                'n.reference_type',
                'n.is_read',
                'n.created_at',
                'n.updated_at'
            )
            ->first();

        if (!$data) {
            return response()->json([
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        $authUser = $request->user();

        if ($authUser && (int) $authUser->user_id !== (int) $data->user_id) {
            return response()->json([
                'message' => 'Tidak boleh mengakses notifikasi pengguna lain'
            ], 403);
        }

        $data = $this->enrichNotification($data);

        return response()->json([
            'message' => 'Detail notifikasi berhasil diambil',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'notification_type_id' => 'required|exists:notification_types,notification_type_id',
            'title' => 'required|string|max:150',
            'message' => 'required|string',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string|max:50',
        ]);

        $notificationId = DB::table('notifications')->insertGetId([
            'user_id' => $request->user_id,
            'notification_type_id' => $request->notification_type_id,
            'title' => $request->title,
            'message' => $request->message,
            'reference_id' => $request->reference_id,
            'reference_type' => $request->reference_type,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'notification_id');

        $this->sendPushNotification(
            $request->user_id,
            $request->title,
            $request->message,
            $notificationId,
            $request->reference_id,
            $request->reference_type,
            $request->notification_type_id
        );

        return response()->json([
            'message' => 'Notifikasi berhasil dibuat',
            'notification_id' => $notificationId
        ], 201);
    }

    public function markAsRead($notificationId)
    {
        $exists = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Notifikasi berhasil ditandai sebagai dibaca'
        ]);
    }

    public function markAllAsRead($userId)
    {
        DB::table('notifications')
            ->where('user_id', $userId)
            ->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca'
        ]);
    }

    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_id' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        DB::table('user_fcm_tokens')
            ->where('fcm_token', $request->fcm_token)
            ->where('user_id', '!=', $user->user_id)
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);

        $existingToken = DB::table('user_fcm_tokens')
            ->where('user_id', $user->user_id)
            ->where('fcm_token', $request->fcm_token)
            ->first();

        if ($existingToken) {
            DB::table('user_fcm_tokens')
                ->where('user_fcm_token_id', $existingToken->user_fcm_token_id)
                ->update([
                    'device_id' => $request->device_id,
                    'platform' => $request->platform,
                    'is_active' => true,
                    'last_seen_at' => now(),
                    'logged_out_at' => null,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('user_fcm_tokens')->insert([
                'user_id' => $user->user_id,
                'fcm_token' => $request->fcm_token,
                'device_id' => $request->device_id,
                'platform' => $request->platform,
                'is_active' => true,
                'last_seen_at' => now(),
                'logged_out_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'FCM token berhasil disimpan',
        ]);
    }

    public function deactivateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();

        DB::table('user_fcm_tokens')
            ->where('user_id', $user->user_id)
            ->where('fcm_token', $request->fcm_token)
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'FCM token berhasil dinonaktifkan',
        ]);
    }

    public function testPush(Request $request, FcmService $fcmService)
    {
        $sent = $fcmService->sendToUser(
            $request->user()->user_id,
            'Tes Notifikasi DiabetAku',
            'Kalau ini muncul di status bar, berarti FCM sudah berhasil.',
            [
                'type' => 'test',
                'source' => 'laravel',
            ]
        );

        return response()->json([
            'message' => $sent ? 'Push berhasil dikirim' : 'Push gagal dikirim',
            'sent' => $sent,
        ]);
    }
}
