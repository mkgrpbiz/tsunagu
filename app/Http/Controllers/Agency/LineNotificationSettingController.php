<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LineNotificationSettingController extends Controller
{
    public function edit(): View
    {
        return view('agency.line_notification_settings.edit', [
            'agency' => Auth::guard('agency')->user(),
            'liffId' => config('services.line.liff_id'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        $data = $request->validate([
            'line_uid' => ['required', 'string', 'max:255'],
            'line_display_name' => ['nullable', 'string', 'max:255'],
        ]);

        $agency->update($data);

        return redirect()->route('agency.line-notification-settings.edit')->with('status', 'LINE通知の連携が完了しました。');
    }

    public function destroy(): RedirectResponse
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        $agency->update(['line_uid' => null, 'line_display_name' => null]);

        return redirect()->route('agency.line-notification-settings.edit')->with('status', 'LINE通知の連携を解除しました。');
    }
}
