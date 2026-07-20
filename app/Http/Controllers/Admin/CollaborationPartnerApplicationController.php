<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationPartnerApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationPartnerApplication;
use App\Models\NotificationMessageSetting;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CollaborationPartnerApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $statusCounts = CollaborationPartnerApplication::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        $applications = CollaborationPartnerApplication::with('agency')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('admin.collaboration_partner_applications.index', [
            'applications' => $applications,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'totalCount' => CollaborationPartnerApplication::count(),
        ]);
    }

    public function show(CollaborationPartnerApplication $collaborationPartnerApplication): View
    {
        return view('admin.collaboration_partner_applications.show', [
            'application' => $collaborationPartnerApplication->load('agency'),
        ]);
    }

    public function updateStatus(Request $request, CollaborationPartnerApplication $collaborationPartnerApplication, LineMessagingService $lineMessaging): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(CollaborationPartnerApplicationStatus::class)],
        ]);

        $previousStatus = $collaborationPartnerApplication->status;
        $collaborationPartnerApplication->update($data);

        if ($previousStatus !== $collaborationPartnerApplication->status) {
            $this->notifyAgency($collaborationPartnerApplication, $lineMessaging);
        }

        return redirect()
            ->route('admin.collaboration-partner-applications.show', $collaborationPartnerApplication)
            ->with('status', 'ステータスを更新しました。');
    }

    private function notifyAgency(CollaborationPartnerApplication $collaborationPartnerApplication, LineMessagingService $lineMessaging): void
    {
        $agency = $collaborationPartnerApplication->agency;

        if (! $agency->line_uid) {
            return;
        }

        $setting = NotificationMessageSetting::forFeature(
            NotificationMessageSetting::FEATURE_COLLABORATION_PARTNER_APPLICATION,
            '共創パートナー申請の審査が完了し、承認となりました。担当者よりZoomでのお打ち合わせについてご連絡いたします。',
            '共創パートナー申請の審査が完了し、今回は見送りとなりました。',
        );

        $message = match ($collaborationPartnerApplication->status) {
            CollaborationPartnerApplicationStatus::Approved => $setting->approved_message,
            CollaborationPartnerApplicationStatus::Rejected => $setting->rejected_message,
            default => null,
        };

        if ($message) {
            $lineMessaging->sendPush($agency->line_uid, $message);
        }
    }
}
