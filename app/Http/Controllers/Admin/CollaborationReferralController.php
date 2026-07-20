<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationReferral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function toggleStatus(CollaborationReferral $collaborationReferral): RedirectResponse
    {
        $collaborationReferral->update([
            'status' => $collaborationReferral->status === CollaborationReferralStatus::Handled
                ? CollaborationReferralStatus::Pending
                : CollaborationReferralStatus::Handled,
        ]);

        return redirect()->route('admin.collaboration-referrals.show', $collaborationReferral)->with('status', 'ステータスを更新しました。');
    }
}
