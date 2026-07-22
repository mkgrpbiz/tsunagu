<?php

namespace App\Http\Controllers\Public;

use App\Enums\InquiryStatus;
use App\Enums\LineChannel;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InviteLink;
use App\Models\LineUser;
use App\Models\Project;
use App\Services\LineMessagingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplyController extends Controller
{
    public function show(InviteLink $inviteLink): View
    {
        $inviteLink->load('project');

        return view('public.apply.show', [
            'inviteLink' => $inviteLink,
            'project' => $inviteLink->project,
            'liffId' => config('services.line_customer.liff_id'),
            'officialAccountId' => config('services.line_customer.official_account_id'),
            'result' => $inviteLink->project->status === ProjectStatus::Published ? null : 'unavailable',
            'offerText' => $this->offerText($inviteLink->project),
        ]);
    }

    public function store(Request $request, InviteLink $inviteLink, LineMessagingService $lineMessaging): View
    {
        $inviteLink->load('project');

        if ($inviteLink->project->status !== ProjectStatus::Published) {
            abort(404);
        }

        $data = $request->validate([
            'line_uid' => ['required', 'string', 'max:255'],
            'line_display_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'is_friend' => ['nullable', 'boolean'],
        ]);

        $lineUser = LineUser::firstOrCreate(
            ['line_uid' => $data['line_uid']],
            ['display_name' => $data['line_display_name'] ?? null],
        );

        if (! empty($data['line_display_name']) && $lineUser->display_name !== $data['line_display_name']) {
            $lineUser->update(['display_name' => $data['line_display_name']]);
        }

        $isFriend = $request->boolean('is_friend');

        if ($isFriend && ! $lineUser->is_friend) {
            $lineUser->update(['is_friend' => true, 'followed_at' => now()]);
        }

        $inquiry = Inquiry::create([
            'agency_id' => $inviteLink->agency_id,
            'project_id' => $inviteLink->project_id,
            'invite_link_id' => $inviteLink->id,
            'line_user_id' => $lineUser->id,
            'name' => $data['name'],
            'name_kana' => $data['name_kana'],
            'email' => $data['email'],
            'status' => InquiryStatus::New,
            'inquired_at' => now(),
        ]);

        $result = 'not_friend';

        if ($isFriend && filled($inviteLink->project->line_auto_message)) {
            $sent = $lineMessaging->sendPush(LineChannel::Customer, $lineUser->line_uid, $inviteLink->project->line_auto_message);

            if ($sent) {
                $inquiry->update(['guidance_sent_at' => now(), 'status' => InquiryStatus::Guided]);
            } else {
                $inquiry->update(['status' => InquiryStatus::GuidanceFailed]);
            }

            $result = 'friend';
        }

        return view('public.apply.show', [
            'inviteLink' => $inviteLink,
            'project' => $inviteLink->project,
            'liffId' => config('services.line_customer.liff_id'),
            'officialAccountId' => config('services.line_customer.official_account_id'),
            'result' => $result,
            'offerText' => $this->offerText($inviteLink->project),
        ]);
    }

    private function offerText(Project $project): string
    {
        return trim(str_replace(
            ['✅【お申し込みはこちら】', '{invite_url}'],
            '',
            (string) $project->recruitment_template
        ));
    }
}
