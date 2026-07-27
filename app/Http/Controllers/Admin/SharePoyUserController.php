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
        return view('admin.sharepoy_users.show', [
            'sharepoyUser' => $sharepoyUser,
            'depositRecords' => $sharepoyUser->depositRecords()->with('inquiry')->latest('deposit_date')->get(),
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
        $rawLines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $lines = [];

        foreach ($rawLines as $lineText) {
            $lineText = trim($lineText);

            if ($lineText === '') {
                continue;
            }

            // タブ区切りが基本だが、手入力で紛れ込んだ半角スペース(2個以上連続)も列区切りとして許容する
            $columns = preg_split('/\t+| {2,}/', $lineText) ?: [];
            $userId = trim($columns[0] ?? '');
            $referrerId = trim($columns[1] ?? '');
            $name = trim($columns[2] ?? '');
            $nameKana = trim($columns[3] ?? '');

            $error = null;
            if ($userId === '' || $name === '' || $nameKana === '') {
                $error = 'ユーザーID・名前・フリガナのいずれかが空です';
            }

            $lines[] = [
                'raw' => $lineText,
                'sharepoy_user_id' => $userId,
                'referrer_sharepoy_user_id' => $referrerId !== '' ? $referrerId : null,
                'name' => $name,
                'name_kana' => $nameKana,
                'error' => $error,
            ];
        }

        return $lines;
    }
}
