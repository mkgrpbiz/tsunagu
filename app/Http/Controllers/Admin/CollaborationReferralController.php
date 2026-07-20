<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationReferral;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CollaborationReferralController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $statusCounts = CollaborationReferral::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        $referrals = CollaborationReferral::with('agency')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('admin.collaboration_referrals.index', [
            'referrals' => $referrals,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'totalCount' => CollaborationReferral::count(),
        ]);
    }

    public function show(CollaborationReferral $collaborationReferral): View
    {
        return view('admin.collaboration_referrals.show', [
            'referral' => $collaborationReferral->load('agency'),
        ]);
    }

    public function updateStatus(Request $request, CollaborationReferral $collaborationReferral, LineMessagingService $lineMessaging): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(CollaborationReferralStatus::class)],
        ]);

        $previousStatus = $collaborationReferral->status;
        $collaborationReferral->update($data);

        if ($previousStatus !== $collaborationReferral->status) {
            $this->notifyAgency($collaborationReferral, $lineMessaging);
        }

        return redirect()->route('admin.collaboration-referrals.show', $collaborationReferral)->with('status', 'ステータスを更新しました。');
    }

    private function notifyAgency(CollaborationReferral $collaborationReferral, LineMessagingService $lineMessaging): void
    {
        $agency = $collaborationReferral->agency;

        if (! $agency->line_uid) {
            return;
        }

        $message = match ($collaborationReferral->status) {
            CollaborationReferralStatus::Approved => "共創先紹介（{$collaborationReferral->referred_name}様）の審査が完了し、承認となりました。",
            CollaborationReferralStatus::Rejected => "共創先紹介（{$collaborationReferral->referred_name}様）の審査が完了し、今回は見送りとなりました。",
            default => null,
        };

        if ($message) {
            $lineMessaging->sendPush($agency->line_uid, $message);
        }
    }
}
