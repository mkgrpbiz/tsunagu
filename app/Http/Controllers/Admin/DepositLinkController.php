<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Project;
use App\Services\ContractLinkingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepositLinkController extends Controller
{
    public function __construct(private readonly ContractLinkingService $contractLinkingService)
    {
    }

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
            'everyProject' => Project::orderBy('name')->get(),
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

        if (! $this->contractLinkingService->linkInquiry($inquiry, $data['lines'])) {
            return back()->with('error', 'この問い合わせにはすでに着金が紐付けられています。');
        }

        return redirect()
            ->route('admin.deposit-links.index', $request->only(['category_id', 'project_id', 'q']))
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
            ->route('admin.deposit-links.index')
            ->with('status', '該当なし成果を追加し、着金を紐付けました。');
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

        return redirect()->route('admin.deposit-links.index')->with('status', $status);
    }

    /**
     * @return array{matched: array<int, array{raw: string, inquiry: Inquiry, tsunagu_price: int, agency_price: int, count: int}>, unmatched: array<int, array{raw: string, reason: string}>}
     */
    private function parseBulkText(int $projectId, string $text): array
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
