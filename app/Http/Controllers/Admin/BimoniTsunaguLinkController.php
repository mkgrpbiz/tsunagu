<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Project;
use App\Services\ContractLinkingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BimoniTsunaguLinkController extends Controller
{
    private const UNIT_PRICE_RULES = [
        1000 => ['tsunagu' => 1000, 'agency' => 800],
        500 => ['tsunagu' => 500, 'agency' => 400],
    ];

    private const TARGET_PROJECT_NAME = 'BIMONI【募集モニター30件以上】';

    public function __construct(private readonly ContractLinkingService $contractLinkingService)
    {
    }

    public function index(): View
    {
        $project = Project::findByAnyName(self::TARGET_PROJECT_NAME);

        return view('admin.bimoni_tsunagu_links.index', [
            'projectName' => $project?->name ?? self::TARGET_PROJECT_NAME,
        ]);
    }

    public function bulkPreview(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        return view('admin.bimoni_tsunagu_links.bulk_preview', [
            'pastedText' => $data['pasted_text'],
            'matched' => $result['matched'],
            'candidates' => $result['candidates'],
            'unmatched' => $result['unmatched'],
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
            'accept_candidates' => ['array'],
            'accept_candidates.*' => ['string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);
        $acceptedKeys = collect($data['accept_candidates'] ?? []);

        $linkedCount = 0;
        $blockedCount = 0;

        foreach ($result['matched'] as $match) {
            $lines = collect($match['rows'])->map(fn (array $row) => [
                'tsunagu_unit_price' => $row['tsunagu_price'],
                'agency_unit_price' => $row['agency_price'],
                'count' => $row['count'],
            ])->all();

            $success = $this->contractLinkingService->linkInquiry($match['inquiry'], $lines);

            if ($success) {
                $linkedCount++;
            } else {
                $blockedCount++;
            }
        }

        foreach ($result['candidates'] as $candidateEntry) {
            if (! $acceptedKeys->contains($candidateEntry['key'])) {
                continue;
            }

            $lines = collect($candidateEntry['rows'])->map(fn (array $row) => [
                'tsunagu_unit_price' => $row['tsunagu_price'],
                'agency_unit_price' => $row['agency_price'],
                'count' => $row['count'],
            ])->all();

            $success = $this->contractLinkingService->linkInquiry($candidateEntry['inquiry'], $lines);

            if ($success) {
                $linkedCount++;
            } else {
                $blockedCount++;
            }
        }

        $unmatchedCount = count($result['unmatched'])
            + collect($result['candidates'])->filter(fn (array $c) => ! $acceptedKeys->contains($c['key']))->count();

        $status = "{$linkedCount}件を一括紐付けしました。";
        if ($blockedCount > 0) {
            $status .= "{$blockedCount}件はすでに紐付け済みのためスキップしました。";
        }
        if ($unmatchedCount > 0) {
            $status .= "{$unmatchedCount}件は問い合わせと一致しなかったためスキップしました。";
        }

        return redirect()->route('admin.bimoni-tsunagu-links.index')->with('status', $status);
    }

    /**
     * 名前・フリガナ完全一致しない場合に、片方だけ一致する問い合わせ候補を探す
     * （表記ゆれ・入力ミスの救済用。自動では紐付けず、プレビュー画面でのチェック選択が必要）。
     * 同じバッチ内で既に確定/候補提示済みの問い合わせは除外する（$claimedIdsで判定）。
     */
    private function findCandidate(string $name, string $nameKana, int $projectId, array $claimedIds): ?Inquiry
    {
        $base = Inquiry::with(['project', 'agency'])
            ->where('project_id', $projectId)
            ->whereNotIn('id', $claimedIds)
            ->where(function ($q) {
                $q->whereDoesntHave('contracts')
                    ->orWhereHas('project', fn ($q2) => $q2->where('is_recurring', true));
            })
            ->orderBy('inquired_at');

        $byName = (clone $base)->where('name', $name)->first();
        if ($byName) {
            return $byName;
        }

        if ($nameKana === '') {
            return null;
        }

        return (clone $base)->where('name_kana', $nameKana)->first();
    }

    /**
     * @return array{matched: array<int, array{inquiry: Inquiry, rows: array<int, array{raw: string, memo: string, tsunagu_price: int, agency_price: int, count: int}>}>, candidates: array<int, array{key: string, name: string, nameKana: string, inquiry: Inquiry, rows: array<int, array{raw: string, memo: string, tsunagu_price: int, agency_price: int, count: int}>}>, unmatched: array<int, array{raw: string, reason: string}>}
     */
    private function parseBulkText(string $text): array
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
            // 列: [0]日付(無視) [1]"TSUNAGU"固定文字(無視) [2]名前 [3]フリガナ [4]商品名メモ [5]金額
            $columns = preg_split('/\t+| {2,}/', $lineText) ?: [];
            $name = trim($columns[2] ?? '');
            $nameKana = trim($columns[3] ?? '');
            $memo = trim($columns[4] ?? '');
            $amountRaw = trim($columns[5] ?? '');

            if ($name === '' || $amountRaw === '') {
                $unmatched[] = ['raw' => $lineText, 'reason' => '名前・金額のいずれかが空です'];

                continue;
            }

            $amount = (int) preg_replace('/[^\d]/', '', $amountRaw);
            $rule = self::UNIT_PRICE_RULES[$amount] ?? null;

            if ($rule === null) {
                $unmatched[] = ['raw' => $lineText, 'reason' => "金額が¥1,000/¥500以外です(¥{$amount})"];

                continue;
            }

            $parsedLines[] = [
                'raw' => $lineText,
                'name' => $name,
                'name_kana' => $nameKana,
                'memo' => $memo,
                'tsunagu_price' => $rule['tsunagu'],
                'agency_price' => $rule['agency'],
                'count' => 1,
            ];
        }

        // 同じ人・同じ単価の行は、紐づけ前にまとめる(同じ人が複数行に分かれて貼り付けられるケースがあるため)
        $priceLines = collect($parsedLines)
            ->groupBy(fn (array $line) => implode('|', [$line['name'], $line['name_kana'], $line['tsunagu_price'], $line['agency_price']]))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'raw' => $group->pluck('raw')->implode(' / '),
                    'name' => $first['name'],
                    'name_kana' => $first['name_kana'],
                    'memo' => $first['memo'],
                    'tsunagu_price' => $first['tsunagu_price'],
                    'agency_price' => $first['agency_price'],
                    'count' => $group->sum('count'),
                ];
            })
            ->values();

        // 同じ人が金額違いで複数行に分かれているケース(例: 別商品を¥1,000と¥500で1件ずつ)は、
        // TSUNAGU側の問い合わせが1件しか無くても、その1件にまとめて複数明細を紐付ける
        $personGroups = $priceLines->groupBy(fn (array $line) => $line['name'].'|'.$line['name_kana']);

        $project = Project::findByAnyName(self::TARGET_PROJECT_NAME);

        $matched = [];
        $candidates = [];
        $claimedIds = [];

        foreach ($personGroups as $rows) {
            $first = $rows->first();
            $allRaw = $rows->pluck('raw')->implode(' / ');

            if (! $project) {
                $unmatched[] = ['raw' => $allRaw, 'reason' => '案件「'.self::TARGET_PROJECT_NAME.'」が見つかりません'];

                continue;
            }

            $rowsPayload = $rows->map(fn (array $line) => [
                'raw' => $line['raw'],
                'memo' => $line['memo'],
                'tsunagu_price' => $line['tsunagu_price'],
                'agency_price' => $line['agency_price'],
                'count' => $line['count'],
            ])->values()->all();

            $candidateInquiries = Inquiry::with(['project', 'agency'])
                ->where('project_id', $project->id)
                ->where('name', $first['name'])
                ->when($first['name_kana'] !== '', fn ($q) => $q->where('name_kana', $first['name_kana']))
                ->where(function ($q) {
                    $q->whereDoesntHave('contracts')
                        ->orWhereHas('project', fn ($q2) => $q2->where('is_recurring', true));
                })
                ->orderBy('inquired_at')
                ->get();

            $inquiry = $candidateInquiries->first(fn (Inquiry $c) => ! in_array($c->id, $claimedIds, true));

            if ($inquiry) {
                $claimedIds[] = $inquiry->id;

                $matched[] = [
                    'inquiry' => $inquiry,
                    'rows' => $rowsPayload,
                ];

                continue;
            }

            $candidate = $this->findCandidate($first['name'], $first['name_kana'], $project->id, $claimedIds);

            if ($candidate) {
                $claimedIds[] = $candidate->id;

                $candidates[] = [
                    'key' => $first['name'].'|'.$first['name_kana'],
                    'name' => $first['name'],
                    'nameKana' => $first['name_kana'],
                    'inquiry' => $candidate,
                    'rows' => $rowsPayload,
                ];

                continue;
            }

            $unmatched[] = ['raw' => $allRaw, 'reason' => '一致する問い合わせ候補が見つかりません(名前・フリガナをご確認ください)'];
        }

        return ['matched' => $matched, 'candidates' => $candidates, 'unmatched' => $unmatched];
    }
}
