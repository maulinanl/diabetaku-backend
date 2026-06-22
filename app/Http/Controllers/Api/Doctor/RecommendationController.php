<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    private function createNotification(
        $userId,
        $typeId,
        $title,
        $message,
        $referenceId = null,
        $referenceType = null
    ) {
        if (!$userId || !$typeId) return;

        DB::table('notifications')->insert([
            'user_id' => $userId,
            'notification_type_id' => $typeId,
            'title' => $title,
            'message' => $message,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getRecipientRole($userId)
    {
        $isPatient = DB::table('patients')
            ->where('user_id', $userId)
            ->exists();

        if ($isPatient) {
            return 'Pasien';
        }

        $isFamily = DB::table('families')
            ->where('user_id', $userId)
            ->exists();

        if ($isFamily) {
            return 'Keluarga';
        }

        return 'Penerima';
    }

    public function store(Request $request, $clinicalNoteId)
    {
        $request->validate([
            'recommendations' => 'required|array|min:1',
            'recommendations.*.category' => 'required|in:Obat,Pola Makan,Aktivitas Fisik,Gaya Hidup,Lainnya',
            'recommendations.*.recommendation_text' => 'required|string',
            'recipient_user_ids' => 'required|array|min:1',
            'recipient_user_ids.*' => 'exists:users,user_id',
        ]);

        $clinicalNote = DB::table('clinical_notes as cn')
            ->join('patients as p', 'cn.patient_id', '=', 'p.patient_id')
            ->join('users as pu', 'p.user_id', '=', 'pu.user_id')
            ->join('doctors as d', 'cn.doctor_id', '=', 'd.doctor_id')
            ->join('users as du', 'd.user_id', '=', 'du.user_id')
            ->where('cn.clinical_note_id', $clinicalNoteId)
            ->select(
                'cn.clinical_note_id',
                'cn.patient_id',
                'cn.doctor_id',
                'p.user_id as patient_user_id',
                'pu.full_name as patient_name',
                'du.user_id as doctor_user_id',
                'du.full_name as doctor_name'
            )
            ->first();

        if (!$clinicalNote) {
            return response()->json([
                'message' => 'Catatan klinis tidak ditemukan'
            ], 404);
        }

        $notificationTypeId = DB::table('notification_types')
            ->where('notification_type_name', 'Rekomendasi Dokter')
            ->value('notification_type_id');

        $recommendationIds = DB::transaction(function () use (
            $request,
            $clinicalNoteId,
            $clinicalNote,
            $notificationTypeId
        ) {
            $recommendationIds = [];

            foreach ($request->recommendations as $item) {
                $recommendationId = DB::table('recommendations')->insertGetId([
                    'clinical_note_id' => $clinicalNoteId,
                    'category' => $item['category'],
                    'recommendation_text' => $item['recommendation_text'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'recommendation_id');

                $recommendationIds[] = $recommendationId;

                foreach ($request->recipient_user_ids as $userId) {
                    DB::table('recommendation_recipients')->updateOrInsert(
                        [
                            'recommendation_id' => $recommendationId,
                            'user_id' => $userId,
                        ],
                        [
                            'is_read' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            foreach ($request->recipient_user_ids as $userId) {
                $role = $this->getRecipientRole($userId);

                $message = $role === 'Keluarga'
                    ? 'Dr. ' . $clinicalNote->doctor_name . ' mengirim rekomendasi baru untuk ' . $clinicalNote->patient_name . '.'
                    : 'Dr. ' . $clinicalNote->doctor_name . ' mengirim rekomendasi baru untuk Anda.';

                $this->createNotification(
                    $userId,
                    $notificationTypeId,
                    'Rekomendasi Dokter',
                    $message,
                    $clinicalNoteId,
                    'recommendation'
                );
            }

            $this->createNotification(
                $clinicalNote->doctor_user_id,
                $notificationTypeId,
                'Rekomendasi Terkirim',
                'Rekomendasi untuk ' . $clinicalNote->patient_name . ' berhasil dikirim.',
                $clinicalNoteId,
                'clinical_note'
            );

            return $recommendationIds;
        });

        return response()->json([
            'message' => 'Rekomendasi berhasil disimpan dan dikirim',
            'data' => [
                'clinical_note_id' => $clinicalNoteId,
                'recommendation_ids' => $recommendationIds,
            ]
        ], 201);
    }

    public function show($clinicalNoteId)
    {
        $clinicalNote = DB::table('clinical_notes as cn')
            ->join('patients as p', 'cn.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('cn.clinical_note_id', $clinicalNoteId)
            ->select(
                'cn.clinical_note_id',
                'cn.patient_id',
                'cn.doctor_id',
                'u.full_name',
                'u.gender',
                'u.date_of_birth',
                'p.diabetes_type',
                'cn.created_at'
            )
            ->first();

        if (!$clinicalNote) {
            return response()->json([
                'message' => 'Catatan klinis tidak ditemukan'
            ], 404);
        }

        $recommendations = DB::table('recommendations')
            ->where('clinical_note_id', $clinicalNoteId)
            ->select(
                'recommendation_id',
                'category',
                'recommendation_text',
                'created_at'
            )
            ->orderBy('recommendation_id')
            ->get();

        $recipients = DB::table('recommendations as r')
            ->join('recommendation_recipients as rr', 'r.recommendation_id', '=', 'rr.recommendation_id')
            ->join('users as u', 'rr.user_id', '=', 'u.user_id')
            ->leftJoin('patients as p', 'u.user_id', '=', 'p.user_id')
            ->leftJoin('families as f', 'u.user_id', '=', 'f.user_id')
            ->where('r.clinical_note_id', $clinicalNoteId)
            ->select(
                'u.user_id',
                'u.full_name',
                DB::raw("
                    CASE
                        WHEN p.patient_id IS NOT NULL THEN 'Pasien'
                        WHEN f.family_id IS NOT NULL THEN 'Keluarga'
                        ELSE 'Penerima'
                    END as role
                ")
            )
            ->distinct()
            ->get();

        return response()->json([
            'message' => 'Detail rekomendasi berhasil diambil',
            'data' => [
                'patient' => [
                    'patient_id' => $clinicalNote->patient_id,
                    'full_name' => $clinicalNote->full_name,
                    'gender' => $clinicalNote->gender,
                    'date_of_birth' => $clinicalNote->date_of_birth,
                    'diabetes_type' => $clinicalNote->diabetes_type,
                ],
                'recommendations' => $recommendations,
                'recipients' => $recipients,
            ]
        ]);
    }
}
