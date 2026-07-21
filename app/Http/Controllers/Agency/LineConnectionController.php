<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
        try {
            $payload = decrypt($token);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload) || ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        return Agency::find($payload['agency_id'] ?? null);
    }
}
