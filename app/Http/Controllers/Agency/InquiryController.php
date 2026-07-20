<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $agency = Auth::guard('agency')->user();

        $inquiries = Inquiry::where('agency_id', $agency->id)
            ->where('is_legacy_import', false)
            ->with(['project', 'lineUser', 'contract'])
            ->orderByDesc('inquired_at')
            ->get();

        $months = $inquiries->map(fn (Inquiry $inquiry) => $inquiry->inquired_at->format('Y-m'))->unique()->sortDesc()->values();

        $projectId = $request->query('project_id');
        $month = $request->query('month', $months->first());
        $month = $month === 'all' ? null : $month;

        $monthInquiries = $inquiries->when($month, fn ($collection) => $collection->filter(
            fn (Inquiry $inquiry) => $inquiry->inquired_at->format('Y-m') === $month
        ));

        $monthlyTotal = [
            'count' => $monthInquiries->count(),
            'contracted' => $monthInquiries->filter(fn (Inquiry $inquiry) => $inquiry->contract !== null)->count(),
        ];

        $cumulativeTotal = [
            'count' => $inquiries->count(),
            'contracted' => $inquiries->filter(fn (Inquiry $inquiry) => $inquiry->contract !== null)->count(),
        ];

        $projectSummary = $monthInquiries
            ->groupBy(fn (Inquiry $inquiry) => $inquiry->project->name)
            ->map(fn ($group) => [
                'count' => $group->count(),
                'contracted' => $group->filter(fn (Inquiry $inquiry) => $inquiry->contract !== null)->count(),
            ])
            ->sortByDesc('count');

        $filtered = $monthInquiries
            ->when($projectId, fn ($collection) => $collection->where('project_id', (int) $projectId));

        return view('agency.inquiries.index', [
            'inquiries' => $filtered,
            'monthlyTotal' => $monthlyTotal,
            'cumulativeTotal' => $cumulativeTotal,
            'projectSummary' => $projectSummary,
            'projects' => Project::whereIn('id', $inquiries->pluck('project_id')->unique())->orderBy('name')->get(),
            'months' => $months,
            'projectId' => $projectId,
            'month' => $month,
        ]);
    }
}
