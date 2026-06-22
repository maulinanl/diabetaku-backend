<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MasterDataController extends Controller
{
    public function specializations()
    {
        return response()->json([
            'message' => 'Data spesialisasi berhasil diambil',
            'data' => DB::table('specializations')
                ->orderBy('specialization_name')
                ->get()
        ]);
    }

    public function activityTypes()
    {
        return response()->json([
            'message' => 'Data jenis aktivitas berhasil diambil',
            'data' => DB::table('activity_types')
                ->orderBy('activity_name')
                ->get()
        ]);
    }

    public function mealTypes()
    {
        return response()->json([
            'message' => 'Data jenis makan berhasil diambil',
            'data' => DB::table('meal_types')
                ->orderBy('meal_type_name')
                ->get()
        ]);
    }

    public function bloodTypes()
    {
        return response()->json([
            'message' => 'Data golongan darah berhasil diambil',
            'data' => DB::table('blood_types')
                ->orderBy('blood_type')
                ->get()
        ]);
    }

    public function rhesusTypes()
    {
        return response()->json([
            'message' => 'Data rhesus berhasil diambil',
            'data' => DB::table('rhesus_types')
                ->orderBy('rhesus_type')
                ->get()
        ]);
    }

    public function relationTypes()
    {
        return response()->json([
            'message' => 'Data hubungan keluarga berhasil diambil',
            'data' => DB::table('relation_types')
                ->orderBy('relation_name')
                ->get()
        ]);
    }

    public function clinicalParameters()
    {
        return response()->json([
            'message' => 'Data parameter klinis berhasil diambil',
            'data' => DB::table('clinical_parameters')
                ->orderBy('parameter_id')
                ->get()
        ]);
    }

    public function prescriptionMealRules()
    {
        $data = DB::select("SELECT unnest(enum_range(NULL::meal_rule_enum)) as value");

        return response()->json([
            'message' => 'Aturan minum berhasil diambil',
            'data' => collect($data)->pluck('value')->values()
        ]);
    }
}
