<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function getByUser($userId)
    {
        $notifications = DB::table('notifications as n')
            ->join('notification_types as nt', 'n.notification_type_id', '=', 'nt.notification_type_id')
            ->where('n.user_id', $userId)
            ->select(
                'n.notification_id',
                'n.user_id',
                'nt.notification_type_name',
                'n.title',
                'n.message',
                'n.reference_id',
                'n.reference_type',
                'n.is_read',
                'n.created_at'
            )
            ->orderByDesc('n.created_at')
            ->get();

        return response()->json([
            'message' => 'Notifikasi berhasil diambil',
            'data' => $notifications
        ]);
    }

    public function markAsRead($notificationId)
    {
        DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->update([
                'is_read' => true,
                'updated_at' => now()
            ]);

        return response()->json([
            'message' => 'Notifikasi berhasil ditandai sebagai dibaca'
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
}
