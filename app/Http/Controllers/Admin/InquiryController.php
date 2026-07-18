<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $inquiries = Inquiry::with(['agency', 'project.category', 'lineUser', 'contract'])
            ->latest('inquired_at')
            ->get();

        $months = $inquiries->map(fn (Inquiry $inquiry) => $inquiry->inquired_at->format('Y-m'))->unique()->sortDesc()->values();

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

        return view('admin.inquiries.index', [
            'inquiries' => $monthInquiries->values(),
            'monthlyTotal' => $monthlyTotal,
            'cumulativeTotal' => $cumulativeTotal,
            'months' => $months,
            'month' => $month,
        ]);
    }

    public function toggleLost(Inquiry $inquiry): RedirectResponse
    {
        if ($inquiry->status === InquiryStatus::Contracted) {
            return back()->with('error', '着金済みの問い合わせのステータスは変更できません。');
        }

        $inquiry->update([
            'status' => $inquiry->status === InquiryStatus::Lost ? InquiryStatus::New : InquiryStatus::Lost,
        ]);

        return redirect()->route('admin.inquiries.index')->with('status', 'ステータスを更新しました。');
    }
}
