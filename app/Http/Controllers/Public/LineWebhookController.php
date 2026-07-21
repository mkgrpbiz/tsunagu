<?php

namespace App\Http\Controllers\Public;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\LineUser;
use App\Services\LineMessagingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LineWebhookController extends Controller
{
    public function handle(Request $request, LineMessagingService $lineMessaging): Response
    {
        if (! $this->hasValidSignature($request)) {
            abort(403, 'Invalid signature.');
        }

        $events = $request->input('events', []);

        foreach ($events as $event) {
            $type = $event['type'] ?? null;
            $lineUid = $event['source']['userId'] ?? null;

            if (! $lineUid) {
                continue;
            }

            if ($type === 'follow') {
                $this->handleFollow($lineUid, $lineMessaging);
            } elseif ($type === 'unfollow') {
                $this->handleUnfollow($lineUid);
            }
        }

        return response('OK');
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = config('services.line.channel_secret');

        if (blank($secret)) {
            return true;
        }

        $signature = $request->header('X-Line-Signature', '');
        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($expected, $signature);
    }

    private function handleFollow(string $lineUid, LineMessagingService $lineMessaging): void
    {
        $lineUser = LineUser::firstOrCreate(
            ['line_uid' => $lineUid],
            ['is_friend' => true, 'followed_at' => now()],
        );

        $lineUser->update(['is_friend' => true, 'followed_at' => now()]);

        $pendingInquiries = Inquiry::with('project')
            ->where('line_user_id', $lineUser->id)
            ->whereNull('guidance_sent_at')
            ->get();

        foreach ($pendingInquiries as $inquiry) {
            if (blank($inquiry->project->line_auto_message)) {
                continue;
            }

            $sent = $lineMessaging->sendPush($lineUid, $inquiry->project->line_auto_message);

            if ($sent) {
                $inquiry->update(['guidance_sent_at' => now(), 'status' => InquiryStatus::Guided]);
            } else {
                $inquiry->update(['status' => InquiryStatus::GuidanceFailed]);
            }
        }
    }

    private function handleUnfollow(string $lineUid): void
    {
        $lineUser = LineUser::where('line_uid', $lineUid)->first();

        $lineUser?->update(['is_friend' => false, 'unfollowed_at' => now()]);
    }
}
