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
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LineConnectionController extends Controller
{
    public function edit(): View
    {
        return view('agency.line_connection.edit', [
            'agency' => Auth::guard('agency')->user(),
            'liffId' => config('services.line_partner.liff_id'),
        ]);
    }

    /**
     * LIFF経由でLINEアプリ内に開き直されると元のログインセッションが引き継がれないため、
     * この着地ページはログイン状態に依存せず、connect_tokenだけでパートナーを特定する。
     */
    public function liffCallback(Request $request): View
    {
        return view('agency.line_connection.liff_callback', [
            'liffId' => config('services.line_partner.liff_id'),
            'connectToken' => $this->resolveConnectToken($request),
        ]);
    }

    /**
     * LIFFの起動URLに?from=...で渡したconnect_tokenは、LINEアプリ内ログインを経由すると
     * ?liff.state=...（さらにURLエンコードされた形）でラップされて戻ってくるため、
     * トップレベルのクエリだけでなくfrom/liff.state内も見て取り出す必要がある。
     */
    private function resolveConnectToken(Request $request): string
    {
        if ($request->filled('connect_token')) {
            return (string) $request->query('connect_token');
        }

        $from = $request->query('from');

        if (! $from && $request->filled('liff_state')) {
            parse_str(ltrim((string) $request->query('liff_state'), '?'), $liffStateParams);
            $from = $liffStateParams['from'] ?? null;
        }

        if (! $from) {
            return '';
        }

        parse_str((string) parse_url((string) $from, PHP_URL_QUERY), $fromParams);

        return (string) ($fromParams['connect_token'] ?? '');
    }

    public function connect(Request $request, LineMessagingService $lineMessaging): RedirectResponse|Response
    {
        $data = $request->validate([
            'connect_token' => ['required', 'string'],
            'line_uid' => ['required', 'string', 'max:255'],
            'line_display_name' => ['nullable', 'string', 'max:255'],
        ]);

        $agency = $this->resolveAgencyFromToken($data['connect_token']);

        if (! $agency) {
            return response()->view('agency.line_connection.expired', [
                'liffId' => config('services.line_partner.liff_id'),
            ], 400);
        }

        $agency->update([
            'line_uid' => $data['line_uid'],
            'line_display_name' => $data['line_display_name'] ?? null,
        ]);

        Auth::guard('agency')->login($agency);

        $setting = NotificationMessageSetting::forFeature(
            NotificationMessageSetting::FEATURE_LINE_CONNECTED,
            'LINE連携が完了しました。今後、審査結果や各種お知らせをこちらのLINEにお届けします。',
            '',
        );

        if ($setting->approved_message) {
            $lineMessaging->sendPush(LineChannel::Partner, $data['line_uid'], $setting->approved_message);
        }

        return redirect()->route('agency.home')->with('status', 'LINE連携が完了しました。');
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

    private function resolveAgencyFromToken(string $token): ?Agency
    {
        $agencyId = Cache::pull(self::cacheKey($token));

        if (! $agencyId) {
            return null;
        }

        return Agency::find($agencyId);
    }

    public static function cacheKey(string $token): string
    {
        return 'agency_line_connect_token:'.$token;
    }
}
