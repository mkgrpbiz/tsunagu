<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Inquiry;
use App\Models\SharePoyDepositRecord;
use App\Models\SharePoyUser;
use App\Services\ContractLinkingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BimoniSharePoyLinkController extends Controller
{
    private const POINTS_PER_LINE = 300;

    private const LABEL = 'BIMONI紹介';

    /**
     * A01（シェアポイ）への一括着金紐付け先project。
     */
    private const DEPOSIT_PROJECT_ID = 2;

    public function __construct(private readonly ContractLinkingService $contractLinkingService)
    {
    }

    public function index(): View
    {
        return view('admin.bimoni_sharepoy_links.index');
    }

    public function bulkPreview(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        $copyText = collect($result['groups'])
            ->map(fn (array $g) => implode("\t", [$g['code'], $g['points'], self::LABEL]))
            ->implode("\n");

        return view('admin.bimoni_sharepoy_links.bulk_preview', [
            'pastedText' => $data['pasted_text'],
            'groups' => $result['groups'],
            'unmatched' => $result['unmatched'],
            'copyText' => $copyText,
            'amountGroups' => $result['amountGroups'],
            'noAmount' => $result['noAmount'],
        ]);
    }

    /**
     * ポイント付与用の履歴記録とは別に、貼り付けデータの金額列を使ってA01（シェアポイ）に
     * 一括着金紐付け（Inquiry+Contract作成）する。SharePoy+ユーザーとの紐付け有無に関わらず、
     * 金額が読み取れた行はすべて対象にする（点数付与の可否とTSUNAGU側の売上計上は別物のため）。
     */
    public function bulkStoreDeposit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->groupByAmount($data['pasted_text']);

        if (empty($result['amountGroups'])) {
            return redirect()->route('admin.bimoni-sharepoy-links.index')->with('error', '金額が読み取れる行がありませんでした。');
        }

        $a01Agency = Agency::where('legacy_code', 'A01')->firstOrFail();

        $inquiry = Inquiry::create([
            'agency_id' => $a01Agency->id,
            'project_id' => self::DEPOSIT_PROJECT_ID,
            'name' => 'BIMONI(SharePoy)一括 '.Carbon::now()->format('Y-m-d H:i'),
            'name_kana' => '',
            'email' => '',
            'status' => InquiryStatus::Contracted,
            'inquired_at' => now(),
            'is_legacy_import' => false,
        ]);

        $lines = collect($result['amountGroups'])->map(fn (array $g) => [
            'tsunagu_unit_price' => $g['amount'],
            'agency_unit_price' => $g['amount'],
            'count' => $g['count'],
        ])->all();

        $this->contractLinkingService->linkInquiry($inquiry, $lines, self::DEPOSIT_PROJECT_ID);

        $totalCount = collect($result['amountGroups'])->sum('count');

        return redirect()->route('admin.bimoni-sharepoy-links.index')->with('status', "{$totalCount}件をA01（シェアポイ）に一括着金紐付けしました。");
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        $savedCount = 0;

        foreach ($result['groups'] as $group) {
            if (! $group['sharePoyUser']) {
                continue;
            }

            foreach ($group['rows'] as $row) {
                SharePoyDepositRecord::create([
                    'sharepoy_user_id' => $group['sharePoyUser']->id,
                    'inquiry_id' => null,
                    'source' => 'bimoni_sharepoy',
                    'deposit_date' => Carbon::now(),
                    'tsunagu_unit_price' => $row['amount'],
                    'agency_unit_price' => 0,
                    'count' => 1,
                    'memo' => $row['memo'],
                ]);
                $savedCount++;
            }
        }

        $unmatchedCount = count($result['unmatched']);

        $status = "{$savedCount}件をSharePoy+ユーザーの着金履歴に記録しました。";
        if ($unmatchedCount > 0) {
            $status .= "{$unmatchedCount}件は一致しなかったためスキップしました。";
        }

        return redirect()->route('admin.bimoni-sharepoy-links.index')->with('status', $status);
    }

    /**
     * @return array<int, string>
     */
    private function splitLines(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', trim($text)) ?: []),
            fn (string $line) => $line !== '',
        ));
    }

    /**
     * タブ区切りが基本だが、手入力で紛れ込んだ半角スペース(2個以上連続)も列区切りとして許容する
     * 列: [0]日付(無視) [1]紹介コード [2]名前 [3]フリガナ [4]商品名メモ [5]金額(参考表示のみ)
     *
     * @return array{code: string, name: string, nameKana: string, memo: string, amount: int}
     */
    private function extractColumns(string $lineText): array
    {
        $columns = preg_split('/\t+| {2,}/', $lineText) ?: [];

        return [
            'code' => trim($columns[1] ?? ''),
            'name' => trim($columns[2] ?? ''),
            'nameKana' => trim($columns[3] ?? ''),
            'memo' => trim($columns[4] ?? ''),
            'amount' => (int) preg_replace('/[^\d]/', '', trim($columns[5] ?? '')),
        ];
    }

    /**
     * @return array{groups: array<int, array{code: string, name: string, nameKana: string, sharePoyUser: ?SharePoyUser, count: int, points: int, rows: array<int, array{raw: string, memo: string, amount: int}>}>, unmatched: array<int, array{raw: string, reason: string}>}
     */
    private function parseBulkText(string $text): array
    {
        $resolvedRows = [];
        $unmatched = [];

        foreach ($this->splitLines($text) as $lineText) {
            ['code' => $code, 'name' => $name, 'nameKana' => $nameKana, 'memo' => $memo, 'amount' => $amount] = $this->extractColumns($lineText);

            if ($name === '' || $code === '') {
                $unmatched[] = ['raw' => $lineText, 'reason' => '紹介コード・名前のいずれかが空です'];

                continue;
            }

            if ($code === 'SHAREPOY') {
                $sharePoyUser = SharePoyUser::where('name', $name)->where('name_kana', $nameKana)->first();

                if (! $sharePoyUser) {
                    $unmatched[] = ['raw' => $lineText, 'reason' => '一致するSharePoy+ユーザーが見つかりません(名前・フリガナをご確認ください)'];

                    continue;
                }

                $resolvedKey = 'user:'.$sharePoyUser->id;
                $resolvedCode = $sharePoyUser->sharepoy_user_id;
            } elseif (str_starts_with($code, 'SP')) {
                $sharePoyUser = null;
                $resolvedKey = 'code:'.$code;
                $resolvedCode = $code;
            } else {
                $unmatched[] = ['raw' => $lineText, 'reason' => '紹介コード列が不正です(SHAREPOYまたはSPから始まるコードのみ対応)'];

                continue;
            }

            $resolvedRows[] = [
                'raw' => $lineText,
                'resolvedKey' => $resolvedKey,
                'code' => $resolvedCode,
                'name' => $name,
                'nameKana' => $nameKana,
                'sharePoyUser' => $sharePoyUser,
                'memo' => $memo,
                'amount' => $amount,
            ];
        }

        $groups = collect($resolvedRows)
            ->groupBy('resolvedKey')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'code' => $first['code'],
                    'name' => $first['name'],
                    'nameKana' => $first['nameKana'],
                    'sharePoyUser' => $first['sharePoyUser'],
                    'count' => $rows->count(),
                    'points' => $rows->count() * self::POINTS_PER_LINE,
                    'rows' => $rows->map(fn (array $r) => [
                        'raw' => $r['raw'],
                        'memo' => $r['memo'],
                        'amount' => $r['amount'],
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $amountResult = $this->groupByAmount($text);

        return ['groups' => $groups, 'unmatched' => $unmatched, 'amountGroups' => $amountResult['amountGroups'], 'noAmount' => $amountResult['noAmount']];
    }

    /**
     * A01（シェアポイ）への着金紐付け用の金額グループ集計。SharePoy+ユーザーとの紐付け可否は見ないため、
     * DB検索なしでテキストだけから完結する（parseBulkTextと違い、SharePoyUser::where(...)を呼ばない）。
     *
     * @return array{amountGroups: array<int, array{amount: int, count: int}>, noAmount: array<int, array{raw: string}>}
     */
    private function groupByAmount(string $text): array
    {
        $depositRows = [];
        $noAmount = [];

        foreach ($this->splitLines($text) as $lineText) {
            ['code' => $code, 'name' => $name, 'amount' => $amount] = $this->extractColumns($lineText);

            if ($name === '' || $code === '') {
                continue;
            }

            if ($code !== 'SHAREPOY' && ! str_starts_with($code, 'SP')) {
                continue;
            }

            if ($amount > 0) {
                $depositRows[] = ['amount' => $amount];
            } else {
                $noAmount[] = ['raw' => $lineText];
            }
        }

        $amountGroups = collect($depositRows)
            ->groupBy('amount')
            ->map(fn ($rows, $amount) => ['amount' => (int) $amount, 'count' => $rows->count()])
            ->sortByDesc('amount')
            ->values()
            ->all();

        return ['amountGroups' => $amountGroups, 'noAmount' => $noAmount];
    }
}
