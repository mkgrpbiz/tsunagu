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

    /**
     * ユーザー着金履歴反映の確認画面（月末締め翌月10日サイクル）。
     * 名前・フリガナが一致したSharePoy+ユーザー分を金額グループで集計表示し、
     * 片方だけ一致する候補はチェックボックスで個別に選べるようにする。
     */
    public function historyConfirm(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        $matchedRows = collect($result['historyMatched'])->flatMap(fn (array $g) => $g['rows']);

        $recordAmountGroups = $matchedRows
            ->groupBy('amount')
            ->map(fn ($rows, $amount) => ['amount' => (int) $amount, 'count' => $rows->count()])
            ->sortByDesc('amount')
            ->values()
            ->all();

        return view('admin.bimoni_sharepoy_links.history_confirm', [
            'pastedText' => $data['pasted_text'],
            'recordAmountGroups' => $recordAmountGroups,
            'recordCount' => $matchedRows->count(),
            'candidates' => $result['historyCandidates'],
            'noMatchCount' => $result['historyNoMatchCount'],
        ]);
    }

    /**
     * ユーザー着金履歴反映を実行する。SharePoy+ユーザーとの紐付け分のみが対象で、
     * 紹介ポイント・A01一括紐付けとは無関係（サイクルが異なるため別処理）。
     */
    public function historyExecute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
            'accept_candidates' => ['array'],
            'accept_candidates.*' => ['string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);
        $acceptedKeys = collect($data['accept_candidates'] ?? []);

        $savedCount = 0;

        foreach ($result['historyMatched'] as $group) {
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

        foreach ($result['historyCandidates'] as $candidateEntry) {
            if (! $acceptedKeys->contains($candidateEntry['key'])) {
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

        $unmatchedCount = $result['historyNoMatchCount']
            + collect($result['historyCandidates'])
                ->filter(fn (array $c) => ! $acceptedKeys->contains($c['key']))
                ->sum(fn (array $c) => count($c['rows']));

        $status = "{$savedCount}件をSharePoy+ユーザーの着金履歴に記録しました。";
        if ($unmatchedCount > 0) {
            $status .= "{$unmatchedCount}件は一致しなかったためスキップしました。";
        }

        return redirect()->route('admin.bimoni-sharepoy-links.index')->with('status', $status);
    }

    /**
     * 紹介ポイント・一括紐付けの確認画面（月末締め翌月末サイクル）。
     * SharePoy+へのポイント付与用コピーテキストと、A01一括着金紐付けの内容を表示する。
     */
    public function linkConfirm(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        $copyText = collect($result['groups'])
            ->map(fn (array $g) => implode("\t", [$g['code'], $g['points'], self::LABEL]))
            ->implode("\n");

        return view('admin.bimoni_sharepoy_links.link_confirm', [
            'pastedText' => $data['pasted_text'],
            'groups' => $result['groups'],
            'unmatched' => $result['unmatched'],
            'copyText' => $copyText,
            'amountGroups' => $result['amountGroups'],
            'noAmount' => $result['noAmount'],
            'depositLinkCount' => collect($result['amountGroups'])->sum('count'),
        ]);
    }

    /**
     * A01（シェアポイ）への一括着金紐付けを実行する。紹介ポイントのコピーテキストは
     * 手動でSharePoy+に転記するだけなのでDB操作は不要（ここでは行わない）。
     */
    public function linkExecute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

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

        $depositLinkedCount = collect($result['amountGroups'])->sum('count');

        return redirect()->route('admin.bimoni-sharepoy-links.index')->with('status', "{$depositLinkedCount}件をA01（シェアポイ）に一括着金紐付けしました。");
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
     * 姓名間のスペース（半角・全角とも）の有無だけの表記ゆれを吸収するため、比較前に取り除く。
     */
    private function normalizeForMatch(string $value): string
    {
        return str_replace([' ', '　'], '', $value);
    }

    private function findExactMatch(string $name, string $nameKana): ?SharePoyUser
    {
        $normalizedName = $this->normalizeForMatch($name);
        $normalizedKana = $this->normalizeForMatch($nameKana);

        return SharePoyUser::query()
            ->whereRaw("REPLACE(REPLACE(name, ' ', ''), '　', '') = ?", [$normalizedName])
            ->whereRaw("REPLACE(REPLACE(name_kana, ' ', ''), '　', '') = ?", [$normalizedKana])
            ->first();
    }

    /**
     * 名前・フリガナが完全一致しない場合に、片方だけ一致するSharePoy+ユーザーを候補として探す
     * （表記ゆれ・入力ミスの救済用。自動では紐付けず、確認画面でのチェック選択が必要）。
     */
    private function findCandidate(string $name, string $nameKana): ?SharePoyUser
    {
        $normalizedName = $this->normalizeForMatch($name);
        $normalizedKana = $this->normalizeForMatch($nameKana);

        return SharePoyUser::query()->whereRaw("REPLACE(REPLACE(name, ' ', ''), '　', '') = ?", [$normalizedName])->first()
            ?? SharePoyUser::query()->whereRaw("REPLACE(REPLACE(name_kana, ' ', ''), '　', '') = ?", [$normalizedKana])->first();
    }

    /**
     * 紹介コード単位のグルーピング（コピー用テキスト向け）と、名前・フリガナ単位の個別判定
     * （着金履歴向け）は完全に独立した別軸。同じ紹介コードを複数の別人が共有していることが
     * 普通にあるため、コード単位でグルーピングした結果をそのまま着金履歴の紐付け判定に
     * 使うと、そのコードの代表行（グループの先頭行）の一致結果に他の人まで引きずられてしまう
     * （実際に本番で、あるコードの先頭行が不一致だったせいで、同じコードを使う別人の
     * 完全一致するはずの行まで丸ごと「不一致」扱いになっていた）。
     * そのため着金履歴側は行ごとに必ず独立してSharePoy+ユーザーを検索する。
     *
     * @return array{
     *     groups: array<int, array{code: string, count: int, points: int, rows: array<int, array{raw: string, memo: string, amount: int}>}>,
     *     unmatched: array<int, array{raw: string, reason: string}>,
     *     historyMatched: array<int, array{sharePoyUser: SharePoyUser, rows: array<int, array{raw: string, memo: string, amount: int}>}>,
     *     historyCandidates: array<int, array{key: string, name: string, nameKana: string, candidate: SharePoyUser, rows: array<int, array{raw: string, memo: string, amount: int}>}>,
     *     historyNoMatchCount: int,
     *     amountGroups: array<int, array{amount: int, count: int}>,
     *     noAmount: array<int, array{raw: string}>,
     * }
     */
    private function parseBulkText(string $text): array
    {
        $copyRows = [];
        $unmatched = [];
        $copyUnresolved = [];
        $historyRows = [];

        foreach ($this->splitLines($text) as $lineText) {
            ['code' => $code, 'name' => $name, 'nameKana' => $nameKana, 'memo' => $memo, 'amount' => $amount] = $this->extractColumns($lineText);

            if ($name === '' || $code === '') {
                $unmatched[] = ['raw' => $lineText, 'reason' => '紹介コード・名前のいずれかが空です'];

                continue;
            }

            $isSharePoy = $code === 'SHAREPOY';
            $isSpCode = str_starts_with($code, 'SP');

            if (! $isSharePoy && ! $isSpCode) {
                $unmatched[] = ['raw' => $lineText, 'reason' => '紹介コード列が不正です(SHAREPOYまたはSPから始まるコードのみ対応)'];

                continue;
            }

            // 着金履歴向け: コードに関係なく、行ごとに必ず独立して名前・フリガナで検索する
            $sharePoyUser = $this->findExactMatch($name, $nameKana);
            $candidate = $sharePoyUser ? null : $this->findCandidate($name, $nameKana);

            $historyRows[] = [
                'raw' => $lineText,
                'name' => $name,
                'nameKana' => $nameKana,
                'sharePoyUser' => $sharePoyUser,
                'candidate' => $candidate,
                'memo' => $memo,
                'amount' => $amount,
            ];

            // コピー用テキスト向け: SHAREPOYは実IDが確定した場合のみ、SPコードはそのまま
            // （着金履歴側の集計は$historyRowsだけで完結させ、ここでの$unmatchedへの追加は
            // コピー用テキストが作れないことを表すだけなので、着金履歴のカウントには使わない）
            if ($isSharePoy) {
                if (! $sharePoyUser) {
                    $copyUnresolved[] = ['raw' => $lineText, 'reason' => '一致するSharePoy+ユーザーが見つかりません(名前・フリガナをご確認ください)'];

                    continue;
                }

                $resolvedKey = 'user:'.$sharePoyUser->id;
                $resolvedCode = $sharePoyUser->sharepoy_user_id;
            } else {
                $resolvedKey = 'code:'.$code;
                $resolvedCode = $code;
            }

            $copyRows[] = [
                'raw' => $lineText,
                'resolvedKey' => $resolvedKey,
                'code' => $resolvedCode,
                'memo' => $memo,
                'amount' => $amount,
            ];
        }

        $groups = collect($copyRows)
            ->groupBy('resolvedKey')
            ->map(fn ($rows) => [
                'code' => $rows->first()['code'],
                'count' => $rows->count(),
                'points' => $rows->count() * self::POINTS_PER_LINE,
                'rows' => $rows->map(fn (array $r) => [
                    'raw' => $r['raw'],
                    'memo' => $r['memo'],
                    'amount' => $r['amount'],
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $historyMatched = collect($historyRows)
            ->filter(fn (array $r) => $r['sharePoyUser'])
            ->groupBy(fn (array $r) => $r['sharePoyUser']->id)
            ->map(fn ($rows) => [
                'sharePoyUser' => $rows->first()['sharePoyUser'],
                'rows' => $rows->map(fn (array $r) => [
                    'raw' => $r['raw'],
                    'memo' => $r['memo'],
                    'amount' => $r['amount'],
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $historyCandidates = collect($historyRows)
            ->filter(fn (array $r) => ! $r['sharePoyUser'] && $r['candidate'])
            ->groupBy(fn (array $r) => $r['name'].'|'.$r['nameKana'])
            ->map(function ($rows, $key) {
                $first = $rows->first();

                return [
                    'key' => 'name:'.$key,
                    'name' => $first['name'],
                    'nameKana' => $first['nameKana'],
                    'candidate' => $first['candidate'],
                    'rows' => $rows->map(fn (array $r) => [
                        'raw' => $r['raw'],
                        'memo' => $r['memo'],
                        'amount' => $r['amount'],
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        // 着金履歴側の「一致しなかった」件数は、構造的に処理不能な行（名前・コード空欄、コード形式不正）＋
        // 名前・フリガナ検索で候補すら見つからなかった行のみを数える。SHAREPOYでコピー用の実IDが
        // 確定しなかった行（$copyUnresolved）はコピー用テキスト側だけの事情なので含めない
        // （候補がある場合は別途historyCandidatesで扱われるため、ここに混ぜると二重カウントになる）。
        $historyNoMatchCount = count($unmatched)
            + collect($historyRows)->filter(fn (array $r) => ! $r['sharePoyUser'] && ! $r['candidate'])->count();

        $amountResult = $this->groupByAmount($text);

        return [
            'groups' => $groups,
            'unmatched' => array_merge($unmatched, $copyUnresolved),
            'historyMatched' => $historyMatched,
            'historyCandidates' => $historyCandidates,
            'historyNoMatchCount' => $historyNoMatchCount,
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
