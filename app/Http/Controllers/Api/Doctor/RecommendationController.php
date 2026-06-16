<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
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
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('cn.clinical_note_id', $clinicalNoteId)
            ->select(
                'cn.*',
                'p.patient_id',
                'p.diabetes_type',
                'u.full_name as patient_name'
            )
            ->first();

        if (!$clinicalNote) {
            return response()->json([
                'message' => 'Catatan klinis tidak ditemukan'
            ], 404);
        }

        $doctorUserId = DB::table('doctors')
            ->where('doctor_id', $clinicalNote->doctor_id)
            ->value('user_id');

        $notificationTypeId = DB::table('notification_types')
            ->where('notification_type_name', 'Rekomendasi Dokter')
            ->value('notification_type_id');

        $recommendationIds = DB::transaction(function () use (
            $request,
            $clinicalNoteId,
            $clinicalNote,
            $doctorUserId,
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
                    DB::table('recommendation_recipients')->insert([
                        'recommendation_id' => $recommendationId,
                        'user_id' => $userId,
                        'is_read' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($doctorUserId && $notificationTypeId) {
                DB::table('notifications')->insert([
                    'user_id' => $doctorUserId,
                    'notification_type_id' => $notificationTypeId,
                    'title' => 'Rekomendasi Terkirim',
                    'message' => 'Rekomendasi untuk ' . $clinicalNote->patient_name . ' berhasil dikirim.',
                    'reference_id' => $clinicalNoteId,
                    'reference_type' => 'clinical_note',
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
                DB::raw("CASE
                    WHEN p.patient_id IS NOT NULL THEN 'Pasien'
                    WHEN f.family_id IS NOT NULL THEN 'Keluarga'
                    ELSE 'Penerima'
                END as role")
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
