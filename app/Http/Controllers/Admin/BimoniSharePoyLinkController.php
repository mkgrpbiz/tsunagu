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
     * 着金履歴記録（SharePoy+ユーザー紐付け分）とA01一括着金紐付けをまとめて実行する前の、
     * 最終確認画面。件数の内訳だけを見せ、確定はbulkExecuteで一括して行う。
     */
    public function bulkConfirm(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        $recordCount = collect($result['groups'])
            ->filter(fn (array $g) => $g['sharePoyUser'])
            ->sum(fn (array $g) => count($g['rows']));

        $noMatchCount = count($result['unmatched'])
            + collect($result['groups'])->filter(fn (array $g) => ! $g['sharePoyUser'] && ! $g['candidate'])->sum(fn (array $g) => count($g['rows']));

        return view('admin.bimoni_sharepoy_links.bulk_confirm', [
            'pastedText' => $data['pasted_text'],
            'recordCount' => $recordCount,
            'candidates' => $result['candidates'],
            'noMatchCount' => $noMatchCount,
            'amountGroups' => $result['amountGroups'],
            'depositLinkCount' => collect($result['amountGroups'])->sum('count'),
        ]);
    }

    /**
     * 着金履歴記録とA01一括着金紐付けを1つの操作としてまとめて実行する。
     * SharePoy+ユーザーとの紐付け有無に関わらず、金額が読み取れた行はすべてA01紐付けの対象にする
     * （点数付与の可否とTSUNAGU側の売上計上は別物のため）。
     */
    public function bulkExecute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
            'accept_candidates' => ['array'],
            'accept_candidates.*' => ['string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);
        $acceptedKeys = collect($data['accept_candidates'] ?? []);

        $savedCount = 0;
        $noSharePoyUserCount = 0;

        foreach ($result['groups'] as $group) {
            $sharePoyUser = $group['sharePoyUser'];

            if (! $sharePoyUser && $group['candidate'] && $acceptedKeys->contains($group['resolvedKey'])) {
                $sharePoyUser = $group['candidate'];
            }

            if (! $sharePoyUser) {
                $noSharePoyUserCount += count($group['rows']);

                continue;
            }

            foreach ($group['rows'] as $row) {
                SharePoyDepositRecord::create([
                    'sharepoy_user_id' => $sharePoyUser->id,
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

        // groups由来の候補（SPコード行）は上のループで処理済みなので、SHAREPOY専用の候補（keyが"name:"始まり）だけを処理する
        foreach ($result['candidates'] as $candidateEntry) {
            if (! str_starts_with($candidateEntry['key'], 'name:')) {
                continue;
            }

            if (! $acceptedKeys->contains($candidateEntry['key'])) {
                $noSharePoyUserCount += count($candidateEntry['rows']);

                continue;
            }

            foreach ($candidateEntry['rows'] as $row) {
                SharePoyDepositRecord::create([
                    'sharepoy_user_id' => $candidateEntry['candidate']->id,
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

        $depositLinkedCount = 0;

        if (! empty($result['amountGroups'])) {
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

            $depositLinkedCount = collect($result['amountGroups'])->sum('count');
        }

        $unmatchedCount = count($result['unmatched']) + $noSharePoyUserCount;

        $status = "{$savedCount}件をSharePoy+ユーザーの着金履歴に記録し、{$depositLinkedCount}件をA01（シェアポイ）に一括着金紐付けしました。";
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
     * 名前・フリガナが完全一致しない場合に、片方だけ一致するSharePoy+ユーザーを候補として探す
     * （表記ゆれ・入力ミスの救済用。自動では紐付けず、確認画面でのチェック選択が必要）。
     */
    private function findCandidate(string $name, string $nameKana): ?SharePoyUser
    {
        return SharePoyUser::where('name', $name)->first()
            ?? SharePoyUser::where('name_kana', $nameKana)->first();
    }

    /**
     * @return array{groups: array<int, array{resolvedKey: string, code: string, name: string, nameKana: string, sharePoyUser: ?SharePoyUser, candidate: ?SharePoyUser, count: int, points: int, rows: array<int, array{raw: string, memo: string, amount: int}>}>, unmatched: array<int, array{raw: string, reason: string}>, candidates: array<int, array{key: string, name: string, nameKana: string, candidate: SharePoyUser, rows: array<int, array{raw: string, memo: string, amount: int}>}>}
     */
    private function parseBulkText(string $text): array
    {
        $resolvedRows = [];
        $unmatched = [];
        $candidateOnlyRows = [];

        foreach ($this->splitLines($text) as $lineText) {
            ['code' => $code, 'name' => $name, 'nameKana' => $nameKana, 'memo' => $memo, 'amount' => $amount] = $this->extractColumns($lineText);

            if ($name === '' || $code === '') {
                $unmatched[] = ['raw' => $lineText, 'reason' => '紹介コード・名前のいずれかが空です'];

                continue;
            }

            if ($code === 'SHAREPOY') {
                $sharePoyUser = SharePoyUser::where('name', $name)->where('name_kana', $nameKana)->first();

                if (! $sharePoyUser) {
                    $candidate = $this->findCandidate($name, $nameKana);

                    if (! $candidate) {
                        $unmatched[] = ['raw' => $lineText, 'reason' => '一致するSharePoy+ユーザーが見つかりません(名前・フリガナをご確認ください)'];

                        continue;
                    }

                    // 紹介コード用の実IDが確定していないためコピー用一覧には含めず、
                    // 着金履歴の候補としてのみ持ち回る（確認画面でのチェック選択が必要）
                    $candidateOnlyRows[] = [
                        'key' => 'name:'.$name.'|'.$nameKana,
                        'name' => $name,
                        'nameKana' => $nameKana,
                        'candidate' => $candidate,
                        'raw' => $lineText,
                        'memo' => $memo,
                        'amount' => $amount,
                    ];

                    continue;
                }

                $resolvedKey = 'user:'.$sharePoyUser->id;
                $resolvedCode = $sharePoyUser->sharepoy_user_id;
                $candidate = null;
            } elseif (str_starts_with($code, 'SP')) {
                // コードは紹介者側の情報でしかなく、着金履歴の紐付け先とは無関係。
                // SHAREPOY行と同様に名前・フリガナでSharePoy+ユーザーを検索する（見つからなくてもコードはそのまま使う）
                $sharePoyUser = SharePoyUser::where('name', $name)->where('name_kana', $nameKana)->first();
                $candidate = $sharePoyUser ? null : $this->findCandidate($name, $nameKana);
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
                'candidate' => $candidate,
                'memo' => $memo,
                'amount' => $amount,
            ];
        }

        $groups = collect($resolvedRows)
            ->groupBy('resolvedKey')
            ->map(function ($rows, $resolvedKey) {
                $first = $rows->first();

                return [
                    'resolvedKey' => $resolvedKey,
                    'code' => $first['code'],
                    'name' => $first['name'],
                    'nameKana' => $first['nameKana'],
                    'sharePoyUser' => $first['sharePoyUser'],
                    'candidate' => $first['candidate'],
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

        $candidatesFromGroups = collect($groups)
            ->filter(fn (array $g) => ! $g['sharePoyUser'] && $g['candidate'])
            ->map(fn (array $g) => [
                'key' => $g['resolvedKey'],
                'name' => $g['name'],
                'nameKana' => $g['nameKana'],
                'candidate' => $g['candidate'],
                'rows' => $g['rows'],
            ]);

        $candidatesFromShareoy = collect($candidateOnlyRows)
            ->groupBy('key')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'key' => $first['key'],
                    'name' => $first['name'],
                    'nameKana' => $first['nameKana'],
                    'candidate' => $first['candidate'],
                    'rows' => $rows->map(fn (array $r) => [
                        'raw' => $r['raw'],
                        'memo' => $r['memo'],
                        'amount' => $r['amount'],
                    ])->values()->all(),
                ];
            });

        $candidates = $candidatesFromGroups->concat($candidatesFromShareoy)->values()->all();

        $amountResult = $this->groupByAmount($text);

        return [
            'groups' => $groups,
            'unmatched' => $unmatched,
            'candidates' => $candidates,
            'amountGroups' => $amountResult['amountGroups'],
            'noAmount' => $amountResult['noAmount'],
        ];
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
