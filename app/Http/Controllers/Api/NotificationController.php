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
            ->where('n.user_id', $userId)
            ->select(
                'n.notification_id',
                'n.user_id',
                'n.notification_type_id',
                DB::raw("COALESCE(nt.notification_type_name, '-') as type"),
                DB::raw("LOWER(REPLACE(COALESCE(nt.notification_type_name, ''), ' ', '_')) as type_code"),
                'n.title',
                'n.message',
                'n.reference_id',
                'n.reference_type',
                'n.is_read',
                'n.created_at',
                'n.updated_at'
            )
            ->orderByDesc('n.created_at')
            ->get();

        return response()->json([
            'message' => 'Notifikasi berhasil diambil',
            'data' => $data
        ]);
    }

    public function show($notificationId)
    {
        $data = DB::table('notifications as n')
            ->leftJoin('notification_types as nt', 'n.notification_type_id', '=', 'nt.notification_type_id')
            ->where('n.notification_id', $notificationId)
            ->select(
                'n.notification_id',
                'n.user_id',
                'n.notification_type_id',
                DB::raw("COALESCE(nt.notification_type_name, '-') as type"),
                DB::raw("LOWER(REPLACE(COALESCE(nt.notification_type_name, ''), ' ', '_')) as type_code"),
                'n.title',
                'n.message',
                'n.reference_id',
                'n.reference_type',
                'n.is_read',
                'n.created_at',
                'n.updated_at'
            )
            ->first();

        if (!$data) {
            return response()->json([
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail notifikasi berhasil diambil',
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
        $exists = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);

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
