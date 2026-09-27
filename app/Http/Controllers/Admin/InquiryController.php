<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Enums\LineChannel;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Inquiry;
use App\Models\Project;
use App\Services\ContractLinkingService;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function __construct(private readonly ContractLinkingService $contractLinkingService)
    {
    }

    public function index(Request $request): View
    {
        $projectId = $request->query('project_id');
        $q = trim((string) $request->query('q'));

        $inquiries = Inquiry::with(['agency', 'project.category', 'lineUser', 'contract', 'contracts'])
            ->where('is_bulk_reflection', false)
            ->latest('inquired_at')
            ->get();

        // データが無くても当月は選択肢・初期選択に必ず含める（前月のまま止まって見えないように）
        $months = $inquiries->map(fn (Inquiry $inquiry) => $inquiry->inquired_at->format('Y-m'))->toBase()
            ->push(now()->format('Y-m'))
            ->unique()->sortDesc()->values();

        $month = $request->query('month', now()->format('Y-m'));
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

        $filtered = $monthInquiries
            ->when($projectId, fn ($collection) => $collection->where('project_id', (int) $projectId))
            ->when($q !== '', fn ($collection) => $collection->filter(
                fn (Inquiry $inquiry) => str_contains((string) $inquiry->name, $q)
                    || str_contains((string) $inquiry->name_kana, $q)
                    || str_contains((string) $inquiry->legacy_line_display_name, $q)
                    || str_contains((string) $inquiry->lineUser?->display_name, $q)
                    || str_contains((string) $inquiry->agency->legacy_code, $q)
            ));

        $allProjects = Project::orderBy('name')->get();

        $perPage = 100;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pagedInquiries = new LengthAwarePaginator(
            $filtered->values()->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()],
        );

        // 着金紐付けフォームで使う「案件変更」の候補（同じパートナー案件 or 同じ取引先の兄弟案件）。
        // 全件ではなく表示中のページ分だけ計算する（累計表示だと件数が多くなりうるため）。
        foreach ($pagedInquiries as $inquiry) {
            if (! $this->isLinkable($inquiry)) {
                continue;
            }

            $partnerAgencyId = $inquiry->project->partner_agency_id;
            $clientName = $inquiry->project->client_name;

            $inquiry->siblingProjects = $allProjects
                ->filter(function (Project $project) use ($inquiry, $partnerAgencyId, $clientName) {
                    if ($project->id === $inquiry->project_id) {
                        return false;
                    }

                    return ($partnerAgencyId && $project->partner_agency_id === $partnerAgencyId)
                        || ($clientName && $project->client_name === $clientName);
                })
                ->values();
        }

        return view('admin.inquiries.index', [
            'inquiries' => $pagedInquiries,
            'monthlyTotal' => $monthlyTotal,
            'cumulativeTotal' => $cumulativeTotal,
            'months' => $months,
            'month' => $month,
            'projects' => $allProjects,
            'projectId' => $projectId,
            'q' => $q,
            'bulkLinkProjects' => Project::where('bulk_link_enabled', true)->orderBy('name')->get(),
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

    public function link(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.tsunagu_unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.agency_unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.count' => ['required', 'integer', 'min:1'],
            'override_project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        if (! $this->contractLinkingService->linkInquiry($inquiry, $data['lines'], $data['override_project_id'] ?? null)) {
            return back()->with('error', 'この問い合わせにはすでに着金が紐付けられています。');
        }

        return redirect()
            ->route('admin.inquiries.index', $request->only(['month', 'project_id', 'q', 'page']))
            ->with('status', '着金を紐付け、ステータスを着金済みに更新しました。');
    }

    public function storeNoReferral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
            'tsunagu_unit_price' => ['required', 'integer', 'min:0'],
            'count' => ['required', 'integer', 'min:1'],
        ]);

        $inquiry = Inquiry::create([
            'agency_id' => Agency::noReferralAgency()->id,
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'name_kana' => $data['name_kana'],
            'email' => '',
            'status' => InquiryStatus::Contracted,
            'inquired_at' => now(),
            'is_legacy_import' => false,
        ]);

        $this->contractLinkingService->linkInquiry($inquiry, [[
            'tsunagu_unit_price' => $data['tsunagu_unit_price'],
            'agency_unit_price' => 0,
            'count' => $data['count'],
        ]]);

        return redirect()
            ->route('admin.inquiries.index')
            ->with('status', '該当なし成果を追加し、着金を紐付けました。');
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

    public function linkBulkPreview(Request $request): View
    {
        $data = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where('bulk_link_enabled', true)],
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseLinkBulkText((int) $data['project_id'], $data['pasted_text']);

        return view('admin.inquiries.link_bulk_preview', [
            'project' => Project::find($data['project_id']),
            'pastedText' => $data['pasted_text'],
            'matched' => $result['matched'],
            'unmatched' => $result['unmatched'],
        ]);
    }

    public function linkBulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where('bulk_link_enabled', true)],
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseLinkBulkText((int) $data['project_id'], $data['pasted_text']);

        $linkedCount = 0;
        $blockedCount = 0;

        foreach ($result['matched'] as $match) {
            $success = $this->contractLinkingService->linkInquiry($match['inquiry'], [[
                'tsunagu_unit_price' => $match['tsunagu_price'],
                'agency_unit_price' => $match['agency_price'],
                'count' => $match['count'],
            ]]);

            if ($success) {
                $linkedCount++;
            } else {
                $blockedCount++;
            }
        }

        $unmatchedCount = count($result['unmatched']);

        $status = "{$linkedCount}件を一括紐付けしました。";
        if ($blockedCount > 0) {
            $status .= "{$blockedCount}件はすでに紐付け済みのためスキップしました。";
        }
        if ($unmatchedCount > 0) {
            $status .= "{$unmatchedCount}件は問い合わせと一致しなかったためスキップしました。";
        }

        return redirect()->route('admin.inquiries.index')->with('status', $status);
    }

    /**
     * 未紐付け、または継続案件（何度でも紐付け可能）かどうか。
     */
    private function isLinkable(Inquiry $inquiry): bool
    {
        return $inquiry->contracts->isEmpty() || $inquiry->project->is_recurring;
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

    /**
     * @return array{matched: array<int, array{raw: string, inquiry: Inquiry, tsunagu_price: int, agency_price: int, count: int}>, unmatched: array<int, array{raw: string, reason: string}>}
     */
    private function parseLinkBulkText(int $projectId, string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $parsedLines = [];
        $unmatched = [];

        foreach ($lines as $lineText) {
            $lineText = trim($lineText);

            if ($lineText === '') {
                continue;
            }

            // タブ区切りが基本だが、手入力で紛れ込んだ半角スペース(2個以上連続)も列区切りとして許容する
            $columns = preg_split('/\t+| {2,}/', $lineText) ?: [];
            $name = trim($columns[0] ?? '');
            $nameKana = trim($columns[1] ?? '');
            $tsunaguPriceRaw = trim($columns[2] ?? '');
            $agencyPriceRaw = trim($columns[3] ?? '');
            $countRaw = trim($columns[4] ?? '');

            if ($name === '' || $tsunaguPriceRaw === '' || $agencyPriceRaw === '') {
                $unmatched[] = ['raw' => $lineText, 'reason' => '名前・単価のいずれかが空です'];

                continue;
            }

            $tsunaguPrice = (int) preg_replace('/[^\d]/', '', $tsunaguPriceRaw);
            $agencyPrice = (int) preg_replace('/[^\d]/', '', $agencyPriceRaw);
            $count = $countRaw !== '' ? (int) preg_replace('/[^\d]/', '', $countRaw) : 1;
            $count = max($count, 1);

            $parsedLines[] = [
                'raw' => $lineText,
                'name' => $name,
                'name_kana' => $nameKana,
                'tsunagu_price' => $tsunaguPrice,
                'agency_price' => $agencyPrice,
                'count' => $count,
            ];
        }

        // 同じ人・同じ単価の行は、紐づけ前にまとめる（同じ人が複数行に分かれて貼り付けられるケースがあるため）
        $combinedLines = collect($parsedLines)
            ->groupBy(fn (array $line) => implode('|', [$line['name'], $line['name_kana'], $line['tsunagu_price'], $line['agency_price']]))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'raw' => $group->pluck('raw')->implode(' / '),
                    'name' => $first['name'],
                    'name_kana' => $first['name_kana'],
                    'tsunagu_price' => $first['tsunagu_price'],
                    'agency_price' => $first['agency_price'],
                    'count' => $group->sum('count'),
                ];
            })
            ->values();

        $matched = [];
        $claimedIds = [];

        foreach ($combinedLines as $line) {
            $candidateInquiries = Inquiry::with(['project', 'agency'])
                ->where('project_id', $projectId)
                ->where('name', $line['name'])
                ->when($line['name_kana'] !== '', fn ($q) => $q->where('name_kana', $line['name_kana']))
                ->where(function ($q) {
                    $q->whereDoesntHave('contracts')
                        ->orWhereHas('project', fn ($q2) => $q2->where('is_recurring', true));
                })
                ->orderBy('inquired_at')
                ->get();

            $inquiry = $candidateInquiries->first(fn (Inquiry $c) => ! in_array($c->id, $claimedIds, true));

            if (! $inquiry) {
                $unmatched[] = ['raw' => $line['raw'], 'reason' => '一致する問い合わせ候補が見つかりません（名前・フリガナをご確認ください）'];

                continue;
            }

            $claimedIds[] = $inquiry->id;

            $matched[] = [
                'raw' => $line['raw'],
                'inquiry' => $inquiry,
                'tsunagu_price' => $line['tsunagu_price'],
                'agency_price' => $line['agency_price'],
                'count' => $line['count'],
            ];
        }

        return ['matched' => $matched, 'unmatched' => $unmatched];
    }
}
