<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SharePoyDepositRecord;
use App\Models\SharePoyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BimoniSharePoyLinkController extends Controller
{
    private const POINTS_PER_LINE = 300;

    private const LABEL = 'BIMONI紹介';

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
        ]);
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
     * @return array{groups: array<int, array{code: string, name: string, nameKana: string, sharePoyUser: ?SharePoyUser, count: int, points: int, rows: array<int, array{raw: string, memo: string, amount: int}>}>, unmatched: array<int, array{raw: string, reason: string}>}
     */
    private function parseBulkText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $resolvedRows = [];
        $unmatched = [];

        foreach ($lines as $lineText) {
            $lineText = trim($lineText);

            if ($lineText === '') {
                continue;
            }

            // タブ区切りが基本だが、手入力で紛れ込んだ半角スペース(2個以上連続)も列区切りとして許容する
            // 列: [0]日付(無視) [1]紹介コード [2]名前 [3]フリガナ [4]商品名メモ [5]金額(参考表示のみ)
            $columns = preg_split('/\t+| {2,}/', $lineText) ?: [];
            $code = trim($columns[1] ?? '');
            $name = trim($columns[2] ?? '');
            $nameKana = trim($columns[3] ?? '');
            $memo = trim($columns[4] ?? '');
            $amount = (int) preg_replace('/[^\d]/', '', trim($columns[5] ?? ''));

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

        return ['groups' => $groups, 'unmatched' => $unmatched];
    }
}
