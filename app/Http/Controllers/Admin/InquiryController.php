<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $inquiries = Inquiry::with(['agency', 'project.category', 'lineUser', 'contract'])
            ->where('is_bulk_reflection', false)
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

    public function resendGuidance(Inquiry $inquiry, LineMessagingService $lineMessaging): RedirectResponse
    {
        if ($inquiry->status !== InquiryStatus::GuidanceFailed) {
            return back()->with('error', 'エラー状態の問い合わせのみ再送信できます。');
        }

        $inquiry->loadMissing(['lineUser', 'project']);

        if (! $inquiry->lineUser || blank($inquiry->project->line_auto_message)) {
            return back()->with('error', 'LINEユーザーまたは案内メッセージが未設定のため再送信できません。');
        }

        $sent = $lineMessaging->sendPush($inquiry->lineUser->line_uid, $inquiry->project->line_auto_message);

        if (! $sent) {
            return back()->with('error', '再送信に失敗しました。しばらくしてから再度お試しください。');
        }

        $inquiry->update(['guidance_sent_at' => now(), 'status' => InquiryStatus::Guided]);

        return redirect()->route('admin.inquiries.index')->with('status', '案内メッセージを再送信しました。');
    }
}
