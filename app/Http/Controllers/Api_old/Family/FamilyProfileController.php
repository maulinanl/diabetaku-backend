<?php

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class FamilyProfileController extends Controller
{
    public function show($familyId)
    {
        $family = DB::table('families as f')
            ->join('users as u', 'f.user_id', '=', 'u.user_id')
            ->where('f.family_id', $familyId)
            ->first();

        return response()->json($family);
    }
}
