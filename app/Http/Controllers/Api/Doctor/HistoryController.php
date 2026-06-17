<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    public function index($doctorId)
    {
        $data = DB::table('clinical_notes as cn')
            ->join('patients as p', 'cn.patient_id', '=', 'p.patient_id')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('recommendations as r', 'cn.clinical_note_id', '=', 'r.clinical_note_id')
            ->where('cn.doctor_id', $doctorId)
            ->select(
                'cn.clinical_note_id',
                'cn.patient_id',
                'u.full_name',
                'u.gender',
                'u.date_of_birth',
                'p.diabetes_type',
                'cn.patient_condition',
                'cn.doctor_note',
                'cn.treatment_plan',
                'cn.follow_up_date',
                'cn.created_at',
                DB::raw('COUNT(r.recommendation_id) as recommendation_count'),

                DB::raw("
                    CASE
                        WHEN COUNT(r.recommendation_id) > 0
                        THEN 'Rekomendasi'
                        ELSE 'Catatan Klinis'
                    END as history_type
                ")
            )
            ->groupBy(
                'cn.clinical_note_id',
                'cn.patient_id',
                'u.full_name',
                'u.gender',
                'u.date_of_birth',
                'p.diabetes_type',
                'cn.patient_condition',
                'cn.doctor_note',
                'cn.treatment_plan',
                'cn.follow_up_date',
                'cn.created_at'
            )
            ->orderByDesc('cn.created_at')
            ->get();

        return response()->json([
            'message' => 'Riwayat dokter berhasil diambil',
            'data' => $data
        ]);
    }
}
