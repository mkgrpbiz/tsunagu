<?php

namespace App\Http\Controllers\Agency;

use App\Enums\LineChannel;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\NotificationMessageSetting;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LineConnectionController extends Controller
{
    public function edit(): View
    {
        return view('agency.line_connection.edit', [
            'agency' => Auth::guard('agency')->user(),
        ]);
    }

    /**
     * LINEアプリ内ブラウザ経由だと元のログインセッションが引き継がれないことがあるため、
     * この着地ページはログイン状態に依存せず、stateに埋め込んだagency_idだけで特定する。
     * stateの検証もブラウザのCookie/ストレージに依存せず、サーバー側の暗号化のみで完結させている。
     */
    public function oauthCallback(Request $request, LineMessagingService $lineMessaging): RedirectResponse|Response
    {
        $agency = $this->resolveAgencyFromState((string) $request->query('state', ''));
        $code = (string) $request->query('code', '');

        if (! $agency || $code === '') {
            return response()->view('agency.line_connection.expired', [], 400);
        }

        $tokenResponse = Http::asForm()->post('https://api.line.me/oauth2/v2.1/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('agency.line-connection.oauth-callback'),
            'client_id' => config('services.line_partner.channel_id'),
            'client_secret' => config('services.line_partner.channel_secret'),
        ]);

        if (! $tokenResponse->successful()) {
            return response()->view('agency.line_connection.expired', [], 400);
        }

        $profileResponse = Http::withToken($tokenResponse->json('access_token'))
            ->get('https://api.line.me/v2/profile');

        if (! $profileResponse->successful()) {
            return response()->view('agency.line_connection.expired', [], 400);
        }

        $agency->update([
            'line_uid' => $profileResponse->json('userId'),
            'line_display_name' => $profileResponse->json('displayName'),
        ]);

        Auth::guard('agency')->login($agency);

        $setting = NotificationMessageSetting::forFeature(
            NotificationMessageSetting::FEATURE_LINE_CONNECTED,
            'LINE連携が完了しました。今後、審査結果や各種お知らせをこちらのLINEにお届けします。',
            '',
        );

        if ($setting->approved_message) {
            $lineMessaging->sendPush(LineChannel::Partner, $profileResponse->json('userId'), $setting->approved_message);
        }

        return redirect()->route('agency.home')->with('status', 'LINE連携が完了しました。');
    }

    private function resolveAgencyFromState(string $state): ?Agency
    {
        try {
            $payload = decrypt($state);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload) || ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        return Agency::find($payload['agency_id'] ?? null);
    }

    public function destroy(): RedirectResponse
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        $agency->update(['line_uid' => null, 'line_display_name' => null]);

        return redirect()->route('agency.line-connection.edit')->with('status', 'LINE連携を解除しました。');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        $agency->update([
            'line_notify_project_info' => $request->boolean('line_notify_project_info'),
            'line_notify_payment' => $request->boolean('line_notify_payment'),
        ]);

        return redirect()->route('agency.line-connection.edit')->with('status', 'LINE通知設定を更新しました。');
    }
}
