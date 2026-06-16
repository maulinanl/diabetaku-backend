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
            'category' => 'required|in:Obat,Pola Makan,Aktivitas Fisik,Gaya Hidup,Lainnya',
            'recommendation_text' => 'required|string',
            'recipient_user_ids' => 'required|array|min:1',
            'recipient_user_ids.*' => 'exists:users,user_id',
        ]);

        $clinicalNote = DB::table('clinical_notes')
            ->where('clinical_note_id', $clinicalNoteId)
            ->first();

        if (!$clinicalNote) {
            return response()->json([
                'message' => 'Catatan klinis tidak ditemukan'
            ], 404);
        }

        $recommendationId = DB::transaction(function () use ($request, $clinicalNoteId) {
            $recommendationId = DB::table('recommendations')->insertGetId(
                [
                    'clinical_note_id' => $clinicalNoteId,
                    'category' => $request->category,
                    'recommendation_text' => $request->recommendation_text,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'recommendation_id'
            );

            foreach ($request->recipient_user_ids as $userId) {
                DB::table('recommendation_recipients')->insert([
                    'recommendation_id' => $recommendationId,
                    'user_id' => $userId,
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $recommendationId;
        });

        return response()->json([
            'message' => 'Rekomendasi berhasil disimpan dan dikirim',
            'data' => [
                'recommendation_id' => $recommendationId
            ]
        ], 201);
    }

    public function show($clinicalNoteId)
    {
        $recommendations = DB::table('recommendations')
            ->where('clinical_note_id', $clinicalNoteId)
            ->orderByDesc('created_at')
            ->get();

        if ($recommendations->isEmpty()) {
            return response()->json([
                'message' => 'Rekomendasi tidak ditemukan'
            ], 404);
        }

        $data = $recommendations->map(function ($recommendation) {
            $recipients = DB::table('recommendation_recipients as rr')
                ->join('users as u', 'rr.user_id', '=', 'u.user_id')
                ->where('rr.recommendation_id', $recommendation->recommendation_id)
                ->select(
                    'rr.user_id',
                    'u.full_name',
                    'u.email',
                    'rr.is_read',
                    'rr.read_at'
                )
                ->get();

            return [
                'recommendation' => $recommendation,
                'recipients' => $recipients,
            ];
        });

        return response()->json([
            'message' => 'Detail rekomendasi berhasil diambil',
            'data' => $data
        ]);
    }
}
