<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationReferral;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CollaborationReferralController extends Controller
{
    public function index(): View
    {
        return view('admin.collaboration_referrals.index', [
            'referrals' => CollaborationReferral::with('agency')
                ->latest()
                ->get(),
        ]);
    }

    public function toggleStatus(CollaborationReferral $collaborationReferral): RedirectResponse
    {
        $collaborationReferral->update([
            'status' => $collaborationReferral->status === CollaborationReferralStatus::Handled
                ? CollaborationReferralStatus::Pending
                : CollaborationReferralStatus::Handled,
        ]);

        return redirect()->route('admin.collaboration-referrals.index')->with('status', 'ステータスを更新しました。');
    }
}
