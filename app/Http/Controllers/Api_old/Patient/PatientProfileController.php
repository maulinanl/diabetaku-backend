<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PatientProfileController extends Controller
{
    public function show($patientId)
    {
        $patient = DB::table('patients as p')
            ->join('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.patient_id', $patientId)
            ->first();

        return response()->json($patient);
    }
}
