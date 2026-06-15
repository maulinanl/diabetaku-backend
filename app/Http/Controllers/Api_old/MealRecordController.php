<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MealRecordController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'input_by_user_id' => 'required|exists:users,user_id',
            'meal_type_id' => 'required|exists:meal_types,meal_type_id',
            'food_description' => 'required|string',
            'carbohydrate_estimate' => 'nullable|numeric',
            'meal_date' => 'required|date',
        ]);

        $id = DB::table('meal_records')->insertGetId([
            'patient_id' => $request->patient_id,
            'input_by_user_id' => $request->input_by_user_id,
            'meal_type_id' => $request->meal_type_id,
            'food_description' => $request->food_description,
            'carbohydrate_estimate' => $request->carbohydrate_estimate,
            'validation_status' => 'Valid',
            'meal_date' => $request->meal_date,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'meal_id');

        return response()->json([
            'message' => 'Data pola makan berhasil ditambahkan',
            'meal_id' => $id
        ], 201);
    }

    public function getByPatient($patientId)
    {
        $records = DB::table('meal_records as mr')
            ->join('meal_types as mt', 'mr.meal_type_id', '=', 'mt.meal_type_id')
            ->where('mr.patient_id', $patientId)
            ->select('mr.*', 'mt.meal_type_name')
            ->orderByDesc('meal_date')
            ->get();

        return response()->json([
            'message' => 'Data pola makan berhasil diambil',
            'data' => $records
        ]);
    }
}
