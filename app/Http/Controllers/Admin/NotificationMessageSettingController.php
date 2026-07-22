<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationMessageSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationMessageSettingController extends Controller
{
    private const DEFAULTS = [
        NotificationMessageSetting::FEATURE_COLLABORATION_PARTNER_APPLICATION => [
            'title' => '共創パートナー申請 - LINE通知設定',
            'back_route' => 'admin.collaboration-partner-applications.index',
            'edit_route' => 'admin.notification-message-settings.collaboration-partner-applications.edit',
            'update_route' => 'admin.notification-message-settings.collaboration-partner-applications.update',
            'placeholder_hint' => null,
            'approved' => '共創パートナー申請の審査が完了し、承認となりました。担当者よりZoomでのお打ち合わせについてご連絡いたします。',
            'rejected' => '共創パートナー申請の審査が完了し、今回は見送りとなりました。',
        ],
        NotificationMessageSetting::FEATURE_PAYMENT_COMPLETED => [
            'title' => '支払い完了 - LINE通知設定',
            'back_route' => 'admin.payments.index',
            'edit_route' => 'admin.notification-message-settings.payments.edit',
            'update_route' => 'admin.notification-message-settings.payments.update',
            'placeholder_hint' => '{amount} と書くと、お振込み金額に置き換わります。',
            'approved' => 'お振込みが完了しました。金額: {amount}円',
            'rejected' => '',
            'single_message' => true,
        ],
        NotificationMessageSetting::FEATURE_AGENCY_REVIEW => [
            'title' => 'パートナー審査結果 - LINE通知設定',
            'back_route' => 'admin.agencies.index',
            'edit_route' => 'admin.notification-message-settings.agencies.edit',
            'update_route' => 'admin.notification-message-settings.agencies.update',
            'placeholder_hint' => null,
            'approved' => 'パートナー登録の審査が完了し、承認となりました。案件一覧など各種機能がご利用いただけます。',
            'rejected' => 'パートナー登録の審査が完了し、今回は承認を見送らせていただきました。',
        ],
        NotificationMessageSetting::FEATURE_LINE_CONNECTED => [
            'title' => 'LINE連携完了 - LINE通知設定',
            'back_route' => 'admin.agencies.index',
            'edit_route' => 'admin.notification-message-settings.line-connected.edit',
            'update_route' => 'admin.notification-message-settings.line-connected.update',
            'placeholder_hint' => null,
            'approved' => 'LINE連携が完了しました。今後、審査結果や各種お知らせをこちらのLINEにお届けします。',
            'rejected' => '',
            'single_message' => true,
        ],
    ];

    public function edit(string $feature): View
    {
        $config = self::DEFAULTS[$feature];
        $setting = NotificationMessageSetting::forFeature($feature, $config['approved'], $config['rejected']);

        return view('admin.notification_message_settings.edit', [
            'setting' => $setting,
            'title' => $config['title'],
            'backRoute' => $config['back_route'],
            'updateRoute' => $config['update_route'],
            'placeholderHint' => $config['placeholder_hint'],
            'singleMessage' => $config['single_message'] ?? false,
        ]);
    }

    public function update(Request $request, string $feature): RedirectResponse
    {
        $config = self::DEFAULTS[$feature];
        $singleMessage = $config['single_message'] ?? false;

        $data = $request->validate([
            'approved_message' => ['required', 'string'],
            'rejected_message' => $singleMessage ? ['nullable', 'string'] : ['required', 'string'],
        ]);

        $setting = NotificationMessageSetting::forFeature($feature, $config['approved'], $config['rejected']);
        $setting->update([
            'approved_message' => $data['approved_message'],
            'rejected_message' => $singleMessage ? '' : $data['rejected_message'],
        ]);

        return redirect()->route($config['edit_route'])->with('status', 'LINE通知の文面を更新しました。');
    }
}
