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
        return view('admin.bimoni_tsunagu_links.index');
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
            'unmatched' => $result['unmatched'],
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

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

        $unmatchedCount = count($result['unmatched']);

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
     * @return array{matched: array<int, array{inquiry: Inquiry, rows: array<int, array{raw: string, memo: string, tsunagu_price: int, agency_price: int, count: int}>}>, unmatched: array<int, array{raw: string, reason: string}>}
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

        $project = Project::where('name', self::TARGET_PROJECT_NAME)->first();

        $matched = [];
        $claimedIds = [];

        foreach ($personGroups as $rows) {
            $first = $rows->first();
            $allRaw = $rows->pluck('raw')->implode(' / ');

            if (! $project) {
                $unmatched[] = ['raw' => $allRaw, 'reason' => '案件「'.self::TARGET_PROJECT_NAME.'」が見つかりません'];

                continue;
            }

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

            if (! $inquiry) {
                $unmatched[] = ['raw' => $allRaw, 'reason' => '一致する問い合わせ候補が見つかりません(名前・フリガナをご確認ください)'];

                continue;
            }

            $claimedIds[] = $inquiry->id;

            $matched[] = [
                'inquiry' => $inquiry,
                'rows' => $rows->map(fn (array $line) => [
                    'raw' => $line['raw'],
                    'memo' => $line['memo'],
                    'tsunagu_price' => $line['tsunagu_price'],
                    'agency_price' => $line['agency_price'],
                    'count' => $line['count'],
                ])->values()->all(),
            ];
        }

        return ['matched' => $matched, 'unmatched' => $unmatched];
    }
}
