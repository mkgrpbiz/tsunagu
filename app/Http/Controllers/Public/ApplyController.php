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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

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

    /**
     * フォーム入力を検証し、LINEログイン(認可コードフロー)へリダイレクトする。
     * 入力内容はブラウザに一切持たせず、暗号化したstateとしてサーバー側だけで受け渡す。
     */
    public function redirectToLine(Request $request, InviteLink $inviteLink): RedirectResponse
    {
        $inviteLink->load('project');

        if ($inviteLink->project->status !== ProjectStatus::Published) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $state = encrypt([
            'invite_link_id' => $inviteLink->id,
            'name' => $data['name'],
            'name_kana' => $data['name_kana'],
            'email' => $data['email'],
            'expires_at' => now()->addMinutes(15)->timestamp,
        ]);

        $authorizeUrl = 'https://access.line.me/oauth2/v2.1/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.line_customer.oauth_channel_id'),
            'redirect_uri' => route('apply.oauth-callback'),
            'state' => $state,
            'scope' => 'profile openid',
        ]);

        return redirect($authorizeUrl);
    }

    /**
     * LINEログイン完了後の戻り先。認可コードの交換・プロフィール取得・問い合わせ作成までを
     * サーバー側だけで完結させ、ブラウザ側での再送信や状態の持ち回しを一切必要としない。
     */
    public function oauthCallback(Request $request, LineMessagingService $lineMessaging): View
    {
        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');

        try {
            $payload = decrypt($state);
        } catch (Throwable) {
            abort(404);
        }

        if (! is_array($payload) || ($payload['expires_at'] ?? 0) < now()->timestamp || $code === '') {
            abort(404);
        }

        $inviteLink = InviteLink::with('project')->find($payload['invite_link_id'] ?? null);

        if (! $inviteLink) {
            abort(404);
        }

        $tokenResponse = Http::asForm()->post('https://api.line.me/oauth2/v2.1/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('apply.oauth-callback'),
            'client_id' => config('services.line_customer.oauth_channel_id'),
            'client_secret' => config('services.line_customer.oauth_channel_secret'),
        ]);

        if (! $tokenResponse->successful()) {
            abort(404);
        }

        $profileResponse = Http::withToken($tokenResponse->json('access_token'))
            ->get('https://api.line.me/v2/profile');

        if (! $profileResponse->successful()) {
            abort(404);
        }

        $displayName = $profileResponse->json('displayName');

        $lineUser = LineUser::firstOrCreate(
            ['line_uid' => $profileResponse->json('userId')],
            ['display_name' => $displayName],
        );

        if ($displayName && $lineUser->display_name !== $displayName) {
            $lineUser->update(['display_name' => $displayName]);
        }

        return $this->completeInquiry(
            $inviteLink,
            $lineUser,
            (string) $payload['name'],
            (string) $payload['name_kana'],
            (string) $payload['email'],
            $lineMessaging,
        );
    }

    /**
     * LIFF未設定のローカル開発環境専用の送信経路。手入力したLINE User ID等をそのまま使う。
     */
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

        if ($request->boolean('is_friend') && ! $lineUser->is_friend) {
            $lineUser->update(['is_friend' => true, 'followed_at' => now()]);
        }

        return $this->completeInquiry($inviteLink, $lineUser, $data['name'], $data['name_kana'], $data['email'], $lineMessaging);
    }

    private function completeInquiry(
        InviteLink $inviteLink,
        LineUser $lineUser,
        string $name,
        string $nameKana,
        string $email,
        LineMessagingService $lineMessaging,
    ): View {
        $inquiry = Inquiry::create([
            'agency_id' => $inviteLink->agency_id,
            'project_id' => $inviteLink->project_id,
            'invite_link_id' => $inviteLink->id,
            'line_user_id' => $lineUser->id,
            'name' => $name,
            'name_kana' => $nameKana,
            'email' => $email,
            'status' => InquiryStatus::New,
            'inquired_at' => now(),
        ]);

        $result = 'not_friend';

        // 友だち状態はliff.getFriendship()のようなその場の呼び出しではなく、
        // Webhookのfollow/unfollowイベントで更新され続けているLineUser.is_friendを信頼する。
        if ($lineUser->is_friend && filled($inviteLink->project->line_auto_message)) {
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
