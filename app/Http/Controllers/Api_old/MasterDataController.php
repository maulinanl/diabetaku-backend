<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MasterDataController extends Controller
{
    public function activityTypes()
    {
        return response()->json([
            'message' => 'Data jenis aktivitas berhasil diambil',
            'data' => DB::table('activity_types')->orderBy('activity_name')->get()
        ]);
    }

    public function mealTypes()
    {
        return response()->json([
            'message' => 'Data jenis makan berhasil diambil',
            'data' => DB::table('meal_types')->orderBy('meal_type_name')->get()
        ]);
    }

    public function specializations()
    {
        return DB::table('specializations')->get();
    }

    public function bloodTypes()
    {
        return DB::table('blood_types')->get();
    }

    public function rhesusTypes()
    {
        return DB::table('rhesus_types')->get();
    }

    public function relationTypes()
    {
        return DB::table('relation_types')->get();
    }
}
