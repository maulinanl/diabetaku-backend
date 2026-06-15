<?php

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show($familyId)
    {
        $profile = DB::table('families as f')
            ->join('users as u', 'f.user_id', '=', 'u.user_id')
            ->where('f.family_id', $familyId)
            ->select(
                'f.family_id',
                'u.user_id',
                'u.full_name',
                'u.email',
                'u.phone_number',
                'u.date_of_birth',
                'u.gender'
            )
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profil keluarga tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Profil keluarga berhasil diambil',
            'data' => $profile
        ]);
    }

    public function update(Request $request, $familyId)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
        ]);

        $family = DB::table('families')
            ->where('family_id', $familyId)
            ->first();

        if (!$family) {
            return response()->json([
                'message' => 'Profil keluarga tidak ditemukan'
            ], 404);
        }

        DB::table('users')
            ->where('user_id', $family->user_id)
            ->update([
                'full_name' => $request->full_name,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Profil keluarga berhasil diperbarui'
        ]);
    }
}
