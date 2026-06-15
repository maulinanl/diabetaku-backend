<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorProfileController extends Controller
{
    public function show($doctorId)
    {
        $doctor = DB::table('doctors as d')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('specializations as s', 'd.specialization_id', '=', 's.specialization_id')
            ->where('d.doctor_id', $doctorId)
            ->select(
                'd.doctor_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.gender',
                'd.str_number',
                'd.institution',
                's.specialization_name'
            )
            ->first();

        return response()->json($doctor);
    }
}
