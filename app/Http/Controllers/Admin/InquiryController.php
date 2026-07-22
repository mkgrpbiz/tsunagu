<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Enums\LineChannel;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Inquiry;
use App\Models\Project;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $sent = $lineMessaging->sendPush(LineChannel::Customer, $inquiry->lineUser->line_uid, $inquiry->project->line_auto_message);

        if (! $sent) {
            return back()->with('error', '再送信に失敗しました。しばらくしてから再度お試しください。');
        }

        $inquiry->update(['guidance_sent_at' => now(), 'status' => InquiryStatus::Guided]);

        return redirect()->route('admin.inquiries.index')->with('status', '案内メッセージを再送信しました。');
    }

    public function bulkPreview(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        return view('admin.inquiries.bulk_preview', [
            'pastedText' => $data['pasted_text'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        foreach ($result['valid'] as $row) {
            $inquiry = Inquiry::create([
                'agency_id' => $row['agency']->id,
                'project_id' => $row['project']->id,
                'name' => $row['name'],
                'name_kana' => $row['name_kana'],
                'email' => $row['email'],
                'legacy_line_display_name' => $row['line_display_name'],
                'status' => InquiryStatus::Guided,
                'inquired_at' => $row['timestamp'] ?? now(),
                'is_legacy_import' => true,
            ]);

            if ($row['timestamp']) {
                $inquiry->forceFill(['created_at' => $row['timestamp'], 'updated_at' => $row['timestamp']])->save();
            }
        }

        $createdCount = count($result['valid']);
        $invalidCount = count($result['invalid']);

        $status = "{$createdCount}件の問い合わせを追加しました。";
        if ($invalidCount > 0) {
            $status .= "{$invalidCount}件はエラーのためスキップしました。";
        }

        return redirect()->route('admin.inquiries.index')->with('status', $status);
    }

    /**
     * @return array{valid: array<int, array<string, mixed>>, invalid: array<int, array<string, mixed>>}
     */
    private function parseBulkText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $valid = [];
        $invalid = [];

        foreach ($lines as $lineText) {
            if (trim($lineText) === '') {
                continue;
            }

            $columns = explode("\t", $lineText);
            $timestampRaw = trim($columns[0] ?? '');

            if ($timestampRaw === 'タイムスタンプ') {
                continue;
            }

            $referralCode = trim($columns[1] ?? '');
            $projectName = trim($columns[2] ?? '');
            $lineDisplayName = trim($columns[3] ?? '');
            $name = trim($columns[4] ?? '');
            $nameKana = trim($columns[5] ?? '');
            $email = trim($columns[6] ?? '');

            $errors = [];

            $agency = $referralCode !== '' ? Agency::where('legacy_code', $referralCode)->first() : null;
            if ($referralCode === '') {
                $errors[] = '紹介コードが空です';
            } elseif (! $agency) {
                $errors[] = "紹介コード「{$referralCode}」に一致するパートナーが見つかりません";
            }

            $project = $projectName !== '' ? Project::findByAnyName($projectName) : null;
            if ($projectName === '') {
                $errors[] = '案件名が空です';
            } elseif (! $project) {
                $errors[] = "案件名「{$projectName}」に一致する案件が見つかりません";
            }

            if ($name === '') {
                $errors[] = 'お名前が空です';
            }
            if ($nameKana === '') {
                $errors[] = 'フリガナが空です';
            }

            if ($email === '') {
                $errors[] = 'メールアドレスが空です';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'メールアドレスの形式が不正です';
            }

            $timestamp = null;
            if ($timestampRaw !== '') {
                try {
                    $timestamp = Carbon::parse($timestampRaw);
                } catch (\Throwable) {
                    $timestamp = null;
                }
            }

            $row = [
                'raw' => $lineText,
                'timestamp' => $timestamp,
                'referral_code' => $referralCode,
                'agency' => $agency,
                'project_name' => $projectName,
                'project' => $project,
                'line_display_name' => $lineDisplayName !== '' ? $lineDisplayName : null,
                'name' => $name,
                'name_kana' => $nameKana,
                'email' => $email,
                'errors' => $errors,
            ];

            if (empty($errors)) {
                $valid[] = $row;
            } else {
                $invalid[] = $row;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }
}
