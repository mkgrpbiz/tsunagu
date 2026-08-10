<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SharePoyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SharePoyUserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $sharepoyUsers = SharePoyUser::when($q !== '', function ($query) use ($q) {
            $query->where(function ($q2) use ($q) {
                $q2->where('sharepoy_user_id', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('name_kana', 'like', "%{$q}%");
            });
        })
            ->orderBy('name_kana')
            ->get();

        return view('admin.sharepoy_users.index', [
            'sharepoyUsers' => $sharepoyUsers,
            'q' => $q,
        ]);
    }

    public function show(SharePoyUser $sharepoyUser): View
    {
        $depositRecords = $sharepoyUser->depositRecords()->with('inquiry')->latest('deposit_date')->get();

        // BIMONI(SharePoy)分は商品ごとに1行ずつ入っていて件数が多くなりがちなので、
        // 着金日×単価でまとめて1行に集約する（他のソースはinquiryと紐づく個別取引なのでそのまま列挙）
        [$bimoniRecords, $otherRecords] = $depositRecords->partition(fn ($r) => $r->source === 'bimoni_sharepoy');

        $bimoniRows = $bimoniRecords
            ->groupBy(fn ($r) => $r->deposit_date->format('Y-m-d').'|'.$r->tsunagu_unit_price)
            ->map(fn ($records) => [
                'depositDate' => $records->first()->deposit_date,
                'sourceLabel' => $records->first()->sourceLabel(),
                'inquiryName' => null,
                'tsunaguUnitPrice' => $records->first()->tsunagu_unit_price,
                'agencyUnitPrice' => $records->first()->agency_unit_price,
                'count' => $records->sum('count'),
            ]);

        $otherRows = $otherRecords->map(fn ($r) => [
            'depositDate' => $r->deposit_date,
            'sourceLabel' => $r->sourceLabel(),
            'inquiryName' => $r->inquiry?->name,
            'tsunaguUnitPrice' => $r->tsunagu_unit_price,
            'agencyUnitPrice' => $r->agency_unit_price,
            'count' => $r->count,
        ]);

        $depositRows = $bimoniRows->concat($otherRows)->sortByDesc('depositDate')->values()->all();

        return view('admin.sharepoy_users.show', [
            'sharepoyUser' => $sharepoyUser,
            'depositRows' => $depositRows,
        ]);
    }

    public function bulkPreview(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        return view('admin.sharepoy_users.bulk_preview', [
            'pastedText' => $data['pasted_text'],
            'lines' => $this->parseBulkText($data['pasted_text']),
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $lines = $this->parseBulkText($data['pasted_text']);

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($lines as $line) {
            if ($line['error'] !== null) {
                continue;
            }

            $existed = SharePoyUser::where('sharepoy_user_id', $line['sharepoy_user_id'])->exists();

            SharePoyUser::updateOrCreate(
                ['sharepoy_user_id' => $line['sharepoy_user_id']],
                [
                    'referrer_sharepoy_user_id' => $line['referrer_sharepoy_user_id'],
                    'name' => $line['name'],
                    'name_kana' => $line['name_kana'],
                ]
            );

            if ($existed) {
                $updatedCount++;
            } else {
                $createdCount++;
            }
        }

        $skippedCount = collect($lines)->filter(fn (array $line) => $line['error'] !== null)->count();

        $status = "{$createdCount}件を新規登録、{$updatedCount}件を更新しました。";
        if ($skippedCount > 0) {
            $status .= "{$skippedCount}件は不正な行のためスキップしました。";
        }

        return redirect()->route('admin.sharepoy-users.index')->with('status', $status);
    }

    /**
     * @return array<int, array{raw: string, sharepoy_user_id: string, referrer_sharepoy_user_id: ?string, name: string, name_kana: string, error: ?string}>
     */
    private function parseBulkText(string $text): array
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $text);
        rewind($handle);

        $lines = [];

        while (($row = fgetcsv($handle, 0, "\t", '"')) !== false) {
            $userId = trim($row[0] ?? '');
            $referrerId = trim($row[1] ?? '');
            $name = trim($row[2] ?? '');
            $nameKana = trim($row[3] ?? '');

            if ($userId === '' && $name === '' && $nameKana === '') {
                continue;
            }

            $error = null;
            if ($userId === '' || $name === '' || $nameKana === '') {
                $error = 'ユーザーID・名前・フリガナのいずれかが空です';
            }

            $lines[] = [
                'raw' => implode("\t", [$userId, $referrerId, $name, $nameKana]),
                'sharepoy_user_id' => $userId,
                'referrer_sharepoy_user_id' => $referrerId !== '' ? $referrerId : null,
                'name' => $name,
                'name_kana' => $nameKana,
                'error' => $error,
            ];
        }

        fclose($handle);

        return $lines;
    }
}
