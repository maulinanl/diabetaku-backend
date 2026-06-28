<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function sendPushNotification(
        $userId,
        $title,
        $message,
        $notificationId = null,
        $referenceId = null,
        $referenceType = null,
        $notificationTypeId = null
    ) {
        $send = function () use (
            $userId,
            $title,
            $message,
            $notificationId,
            $referenceId,
            $referenceType,
            $notificationTypeId
        ) {
            try {
                app(FcmService::class)->sendToUser(
                    $userId,
                    $title,
                    $message,
                    [
                        'notification_id' => $notificationId ?? '',
                        'reference_id' => $referenceId ?? '',
                        'reference_type' => $referenceType ?? '',
                        'notification_type_id' => $notificationTypeId ?? '',
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($send);
        } else {
            $send();
        }
    }

    public function index(Request $request, $userId)
    {
        if ((int) $request->user()->user_id !== (int) $userId) {
            return response()->json([
                'message' => 'Tidak boleh mengakses notifikasi pengguna lain'
            ], 403);
        }

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

    public function show(Request $request, $notificationId)
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

        if ((int) $request->user()->user_id !== (int) $data->user_id) {
            return response()->json([
                'message' => 'Tidak boleh mengakses notifikasi pengguna lain'
            ], 403);
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

        $notificationId = DB::table('notifications')->insertGetId([
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

        $this->sendPushNotification(
            $request->user_id,
            $request->title,
            $request->message,
            $notificationId,
            $request->reference_id,
            $request->reference_type,
            $request->notification_type_id
        );

        return response()->json([
            'message' => 'Notifikasi berhasil dibuat',
            'notification_id' => $notificationId
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

    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_id' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        DB::table('user_fcm_tokens')
            ->where('fcm_token', $request->fcm_token)
            ->where('user_id', '!=', $user->user_id)
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);

        $existingToken = DB::table('user_fcm_tokens')
            ->where('user_id', $user->user_id)
            ->where('fcm_token', $request->fcm_token)
            ->first();

        if ($existingToken) {
            DB::table('user_fcm_tokens')
                ->where('user_fcm_token_id', $existingToken->user_fcm_token_id)
                ->update([
                    'device_id' => $request->device_id,
                    'platform' => $request->platform,
                    'is_active' => true,
                    'last_seen_at' => now(),
                    'logged_out_at' => null,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('user_fcm_tokens')->insert([
                'user_id' => $user->user_id,
                'fcm_token' => $request->fcm_token,
                'device_id' => $request->device_id,
                'platform' => $request->platform,
                'is_active' => true,
                'last_seen_at' => now(),
                'logged_out_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'FCM token berhasil disimpan',
        ]);
    }

    public function deactivateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();

        DB::table('user_fcm_tokens')
            ->where('user_id', $user->user_id)
            ->where('fcm_token', $request->fcm_token)
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'FCM token berhasil dinonaktifkan',
        ]);
    }

    public function testPush(Request $request, FcmService $fcmService)
    {
        $sent = $fcmService->sendToUser(
            $request->user()->user_id,
            'Tes Notifikasi DiabetAku',
            'Kalau ini muncul di status bar, berarti FCM sudah berhasil.',
            [
                'type' => 'test',
                'source' => 'laravel',
            ]
        );

        return response()->json([
            'message' => $sent ? 'Push berhasil dikirim' : 'Push gagal dikirim',
            'sent' => $sent,
        ]);
    }
}
