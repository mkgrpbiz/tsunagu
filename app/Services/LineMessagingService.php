<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineMessagingService
{
    public function sendPush(string $lineUid, string $message): bool
    {
        $token = config('services.line.channel_access_token');

        if (blank($token)) {
            Log::info('LINE push message skipped (no channel access token configured).', [
                'line_uid' => $lineUid,
                'message' => $message,
            ]);

            return true;
        }

        $response = Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
            'to' => $lineUid,
            'messages' => [
                ['type' => 'text', 'text' => $message],
            ],
        ]);

        if ($response->failed()) {
            Log::warning('LINE push message failed.', [
                'line_uid' => $lineUid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
