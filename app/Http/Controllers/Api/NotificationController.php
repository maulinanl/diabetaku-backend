<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index($userId)
    {
        $data = DB::table('notifications as n')
            ->leftJoin('notification_types as nt', 'n.notification_type_id', '=', 'nt.notification_type_id')
            ->leftJoin('families as f', function ($join) {
                $join->on('n.reference_id', '=', 'f.family_id')
                    ->where('n.reference_type', '=', 'family_request');
            })
            ->leftJoin('users as fu', 'f.user_id', '=', 'fu.user_id')
            ->leftJoin('family_patient_relations as fpr', function ($join) {
                $join->on('f.family_id', '=', 'fpr.family_id')
                    ->whereColumn('fpr.patient_id', DB::raw('(select p.patient_id from patients p where p.user_id = n.user_id limit 1)'));
            })
            ->leftJoin('relation_types as rt', 'fpr.relation_type_id', '=', 'rt.relation_type_id')
            ->where('n.user_id', $userId)
            ->select(
                'n.notification_id',
                'n.user_id',
                'n.notification_type_id',
                'nt.notification_type_name as type',
                DB::raw("LOWER(REPLACE(nt.notification_type_name, ' ', '_')) as type_code"),
                'n.title',
                'n.message',
                'n.reference_id',
                'n.reference_type',
                'n.is_read',
                'n.created_at',
                DB::raw("COALESCE(fu.full_name, '') as family_name"),
                DB::raw("COALESCE(rt.relation_name, '') as relation")
            )
            ->orderByDesc('n.created_at')
            ->get();

        return response()->json([
            'message' => 'Notifikasi berhasil diambil',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'notification_type_id' => 'required|exists:notification_types,notification_type_id',
            'title' => 'required|string|max:150',
            'message' => 'required|string',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string|max:50',
        ]);

        $id = DB::table('notifications')->insertGetId([
            'user_id' => $request->user_id,
            'notification_type_id' => $request->notification_type_id,
            'title' => $request->title,
            'message' => $request->message,
            'reference_id' => $request->reference_id,
            'reference_type' => $request->reference_type,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'notification_id');

        return response()->json([
            'message' => 'Notifikasi berhasil dibuat',
            'notification_id' => $id
        ], 201);
    }

    public function markAsRead($notificationId)
    {
        $updated = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json([
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Notifikasi berhasil ditandai sebagai dibaca'
        ]);
    }

    public function markAllAsRead($userId)
    {
        DB::table('notifications')
            ->where('user_id', $userId)
            ->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca'
        ]);
    }
}
