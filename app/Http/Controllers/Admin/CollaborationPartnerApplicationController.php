<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationPartnerApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationPartnerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function toggleStatus(CollaborationPartnerApplication $collaborationPartnerApplication): RedirectResponse
    {
        $collaborationPartnerApplication->update([
            'status' => $collaborationPartnerApplication->status === CollaborationPartnerApplicationStatus::Handled
                ? CollaborationPartnerApplicationStatus::Pending
                : CollaborationPartnerApplicationStatus::Handled,
        ]);

        return redirect()
            ->route('admin.collaboration-partner-applications.show', $collaborationPartnerApplication)
            ->with('status', 'ステータスを更新しました。');
    }
}
