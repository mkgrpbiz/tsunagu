<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LineConnectionController extends Controller
{
    public function edit(): View
    {
        return view('agency.line_connection.edit', [
            'agency' => Auth::guard('agency')->user(),
            'liffId' => config('services.line.liff_id'),
        ]);
    }

    /**
     * LIFF経由でLINEアプリ内に開き直されると元のログインセッションが引き継がれないため、
     * この着地ページはログイン状態に依存せず、connect_tokenだけでパートナーを特定する。
     */
    public function liffCallback(Request $request): View
    {
        Log::info('debug: line-connection liffCallback hit', [
            'full_url' => $request->fullUrl(),
            'connect_token' => (string) $request->query('connect_token', ''),
        ]);

        return view('agency.line_connection.liff_callback', [
            'liffId' => config('services.line.liff_id'),
            'connectToken' => (string) $request->query('connect_token', ''),
        ]);
    }

    public function connect(Request $request): RedirectResponse|Response
    {
        $data = $request->validate([
            'connect_token' => ['required', 'string'],
            'line_uid' => ['required', 'string', 'max:255'],
            'line_display_name' => ['nullable', 'string', 'max:255'],
        ]);

        $agency = $this->resolveAgencyFromToken($data['connect_token']);

        Log::info('debug: line-connection connect() called', [
            'connect_token' => $data['connect_token'],
            'resolved_agency_id' => $agency?->id,
            'line_uid' => $data['line_uid'],
        ]);

        if (! $agency) {
            return response()->view('agency.line_connection.expired', [], 400);
        }

        $agency->update([
            'line_uid' => $data['line_uid'],
            'line_display_name' => $data['line_display_name'] ?? null,
        ]);

        Auth::guard('agency')->login($agency);

        return redirect()->route('agency.home')->with('status', 'LINE連携が完了しました。');
    }

    public function destroy(): RedirectResponse
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        $agency->update(['line_uid' => null, 'line_display_name' => null]);

        return redirect()->route('agency.line-connection.edit')->with('status', 'LINE連携を解除しました。');
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
