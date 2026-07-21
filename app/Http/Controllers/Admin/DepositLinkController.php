<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\ReferralCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepositLinkController extends Controller
{
    public function index(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $projectId = $request->query('project_id');
        $q = trim((string) $request->query('q'));

        $projects = Project::when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->get();

        $candidates = collect();
        $selectedProject = $projectId ? $projects->firstWhere('id', (int) $projectId) : null;

        if ($q !== '') {
            $candidates = Inquiry::with(['agency', 'lineUser', 'project', 'contracts'])
                ->where(function ($query) {
                    $query->whereDoesntHave('contracts')
                        ->orWhereHas('project', fn ($q2) => $q2->where('is_recurring', true));
                })
                ->when($categoryId, fn ($query) => $query->whereHas('project', fn ($q2) => $q2->where('category_id', $categoryId)))
                ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('name_kana', 'like', "%{$q}%")
                        ->orWhere('legacy_line_display_name', 'like', "%{$q}%")
                        ->orWhereHas('lineUser', fn ($q2) => $q2->where('display_name', 'like', "%{$q}%"));
                })
                ->latest('inquired_at')
                ->get();
        }

        return view('admin.deposit_links.index', [
            'categories' => Category::orderBy('name')->get(),
            'projects' => $projects,
            'allProjects' => Project::where('bulk_link_enabled', true)->orderBy('name')->get(),
            'candidates' => $candidates,
            'categoryId' => $categoryId,
            'projectId' => $projectId,
            'q' => $q,
            'selectedProject' => $selectedProject,
        ]);
    }

    public function store(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.tsunagu_unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.agency_unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.count' => ['required', 'integer', 'min:1'],
        ]);

        if (! $this->linkInquiry($inquiry, $data['lines'])) {
            return back()->with('error', 'この問い合わせにはすでに着金が紐付けられています。');
        }

        return redirect()
            ->route('admin.deposit-links.index', $request->only(['category_id', 'project_id', 'q']))
            ->with('status', '着金を紐付け、ステータスを着金済みに更新しました。');
    }

    public function bulkPreview(Request $request): View
    {
        $data = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where('bulk_link_enabled', true)],
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText((int) $data['project_id'], $data['pasted_text']);

        return view('admin.deposit_links.bulk_preview', [
            'project' => Project::find($data['project_id']),
            'pastedText' => $data['pasted_text'],
            'matched' => $result['matched'],
            'unmatched' => $result['unmatched'],
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where('bulk_link_enabled', true)],
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText((int) $data['project_id'], $data['pasted_text']);

        $linkedCount = 0;
        $blockedCount = 0;

        foreach ($result['matched'] as $match) {
            $success = $this->linkInquiry($match['inquiry'], [[
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

        return redirect()->route('admin.deposit-links.index')->with('status', $status);
    }

    /**
     * @return array{matched: array<int, array{raw: string, inquiry: Inquiry, tsunagu_price: int, agency_price: int, count: int}>, unmatched: array<int, array{raw: string, reason: string}>}
     */
    private function parseBulkText(int $projectId, string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $matched = [];
        $unmatched = [];
        $claimedIds = [];

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

            $candidateInquiries = Inquiry::with(['project', 'agency'])
                ->where('project_id', $projectId)
                ->where('name', $name)
                ->when($nameKana !== '', fn ($q) => $q->where('name_kana', $nameKana))
                ->where(function ($q) {
                    $q->whereDoesntHave('contracts')
                        ->orWhereHas('project', fn ($q2) => $q2->where('is_recurring', true));
                })
                ->orderBy('inquired_at')
                ->get();

            $inquiry = $candidateInquiries->first(fn (Inquiry $c) => ! in_array($c->id, $claimedIds, true));

            if (! $inquiry) {
                $unmatched[] = ['raw' => $lineText, 'reason' => '一致する問い合わせ候補が見つかりません（名前・フリガナをご確認ください）'];

                continue;
            }

            $claimedIds[] = $inquiry->id;

            $matched[] = [
                'raw' => $lineText,
                'inquiry' => $inquiry,
                'tsunagu_price' => $tsunaguPrice,
                'agency_price' => $agencyPrice,
                'count' => $count,
            ];
        }

        return ['matched' => $matched, 'unmatched' => $unmatched];
    }

    private function linkInquiry(Inquiry $inquiry, array $lines): bool
    {
        if ($inquiry->contract && ! $inquiry->project->is_recurring) {
            return false;
        }

        $depositDate = Carbon::now();
        $paymentDueDate = $depositDate->copy()->addMonthNoOverflow()->day(5);

        foreach ($lines as $line) {
            $contract = Contract::create([
                'inquiry_id' => $inquiry->id,
                'deposit_date' => $depositDate,
                'deposit_amount' => $line['tsunagu_unit_price'] * $line['count'],
                'agency_reward_amount' => $line['agency_unit_price'] * $line['count'],
                'payment_due_date' => $paymentDueDate,
                'payment_status' => PaymentStatus::Unpaid,
            ]);

            if ($inquiry->agency->referred_by_agency_id) {
                ReferralCommission::create([
                    'contract_id' => $contract->id,
                    'referrer_agency_id' => $inquiry->agency->referred_by_agency_id,
                    'source_agency_id' => $inquiry->agency_id,
                    'amount' => (int) round($contract->agency_reward_amount * 0.1),
                    'payment_due_date' => $paymentDueDate,
                    'payment_status' => PaymentStatus::Unpaid,
                ]);
            }
        }

        $inquiry->update(['status' => InquiryStatus::Contracted]);

        return true;
    }
}
