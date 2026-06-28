<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private function getCredentials(): array
    {
        $credentialsPath = env('FIREBASE_CREDENTIALS');

        if (!$credentialsPath) {
            throw new \Exception('FIREBASE_CREDENTIALS belum diatur di file .env');
        }

        $path = base_path($credentialsPath);

        if (!file_exists($path)) {
            throw new \Exception("Firebase credentials file tidak ditemukan: {$path}");
        }

        $credentials = json_decode(file_get_contents($path), true);

        if (!is_array($credentials)) {
            throw new \Exception('Firebase credentials JSON tidak valid.');
        }

        $requiredKeys = [
            'client_email',
            'private_key',
            'token_uri',
            'project_id',
        ];

        foreach ($requiredKeys as $key) {
            if (empty($credentials[$key])) {
                throw new \Exception("Firebase credentials tidak memiliki key: {$key}");
            }
        }

        return $credentials;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getAccessToken(): ?string
    {
        return Cache::remember('firebase_access_token', 3300, function () {
            try {
                $credentials = $this->getCredentials();

                $now = time();

                $header = [
                    'alg' => 'RS256',
                    'typ' => 'JWT',
                ];

                $claim = [
                    'iss' => $credentials['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => $credentials['token_uri'],
                    'iat' => $now,
                    'exp' => $now + 3600,
                ];

                $jwtHeader = $this->base64UrlEncode(json_encode($header));
                $jwtClaim = $this->base64UrlEncode(json_encode($claim));

                $unsignedJwt = $jwtHeader . '.' . $jwtClaim;

                $privateKey = str_replace("\\n", "\n", $credentials['private_key']);

                $signed = openssl_sign(
                    $unsignedJwt,
                    $signature,
                    $privateKey,
                    OPENSSL_ALGO_SHA256
                );

                if (!$signed) {
                    Log::error('Gagal membuat signature JWT Firebase', [
                        'openssl_error' => openssl_error_string(),
                    ]);

                    return null;
                }

                $jwt = $unsignedJwt . '.' . $this->base64UrlEncode($signature);

                $response = Http::asForm()
                    ->timeout(15)
                    ->post($credentials['token_uri'], [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => $jwt,
                    ]);

                if (!$response->successful()) {
                    Log::error('Gagal ambil Firebase access token', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return null;
                }

                return $response->json('access_token');
            } catch (\Throwable $e) {
                Log::error('Exception saat ambil Firebase access token', [
                    'message' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    private function normalizeData(array $data): array
    {
        $payloadData = [];

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $payloadData[$key] = json_encode($value);
            } else {
                $payloadData[$key] = (string) ($value ?? '');
            }
        }

        return $payloadData;
    }

    public function sendToUser($userId, string $title, string $body, array $data = []): bool
    {
        $token = DB::table('users')
            ->where('user_id', $userId)
            ->value('fcm_token');

        if (!$token) {
            Log::warning('User belum punya FCM token', [
                'user_id' => $userId,
            ]);

            return false;
        }

        $sent = $this->sendToToken($token, $title, $body, $data);

        return $sent;
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::error('FCM access token kosong, push notification dibatalkan.');

            return false;
        }

        try {
            $credentials = $this->getCredentials();

            $projectId = env('FIREBASE_PROJECT_ID') ?: $credentials['project_id'];

            if (!$projectId) {
                Log::error('Firebase project ID tidak ditemukan.');

                return false;
            }

            $payloadData = $this->normalizeData($data);

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(20)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $payloadData,
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => [
                                'channel_id' => 'diabetaku_high_importance',
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Gagal kirim FCM notification', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'title' => $title,
                    'data' => $payloadData,
                ]);

                return false;
            }

            Log::info('FCM notification berhasil dikirim', [
                'response' => $response->json(),
                'title' => $title,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Exception saat kirim FCM notification', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
