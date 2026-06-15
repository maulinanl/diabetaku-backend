<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'clinical_note_id' => 'required|exists:clinical_notes,clinical_note_id',
            'category' => 'required|in:Obat,Pola Makan,Aktivitas Fisik,Gaya Hidup,Lainnya',
            'recommendation_text' => 'required|string',
            'recipient_user_ids' => 'required|array',
            'recipient_user_ids.*' => 'exists:users,user_id',
        ]);

        DB::transaction(function () use ($request) {
            DB::table('recommendations')->insert([
                'clinical_note_id' => $request->clinical_note_id,
                'category' => $request->category,
                'recommendation_text' => $request->recommendation_text,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->recipient_user_ids as $userId) {
                DB::table('recommendation_recipients')->insert([
                    'clinical_note_id' => $request->clinical_note_id,
                    'user_id' => $userId,
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Rekomendasi berhasil dikirim'
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $data = DB::table('recommendations as r')
            ->join('clinical_notes as cn', 'r.clinical_note_id', '=', 'cn.clinical_note_id')
            ->where('cn.patient_id', $patientId)
            ->select('r.*', 'cn.patient_id', 'cn.doctor_id')
            ->orderByDesc('r.created_at')
            ->get();

        return response()->json([
            'message' => 'Rekomendasi berhasil diambil',
            'data' => $data
        ]);
    }
}
