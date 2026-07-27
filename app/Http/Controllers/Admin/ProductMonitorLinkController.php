<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Inquiry;
use App\Services\ContractLinkingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductMonitorLinkController extends Controller
{
    private const TARGET_PROJECT_ID = 3;

    public function __construct(private readonly ContractLinkingService $contractLinkingService)
    {
    }

    public function index(): View
    {
        return view('admin.product_monitor_links.index');
    }

    public function bulkPreview(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkRows($data['pasted_text']);

        return view('admin.product_monitor_links.bulk_preview', [
            'pastedText' => $data['pasted_text'],
            'matched' => $result['matched'],
            'a01' => $result['a01'],
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkRows($data['pasted_text']);

        $linkedCount = 0;
        $blockedCount = 0;

        foreach ($result['matched'] as $match) {
            $success = $this->contractLinkingService->linkInquiry($match['inquiry'], $match['lines']);

            if ($success) {
                $linkedCount++;
            } else {
                $blockedCount++;
            }
        }

        $a01AgencyId = Agency::where('legacy_code', 'A01')->value('id');

        foreach ($result['a01'] as $entry) {
            $inquiry = Inquiry::create([
                'agency_id' => $a01AgencyId,
                'project_id' => self::TARGET_PROJECT_ID,
                'name' => $entry['name'],
                'name_kana' => '',
                'email' => '',
                'status' => InquiryStatus::Contracted,
                'inquired_at' => now(),
                'is_legacy_import' => false,
            ]);

            if ($this->contractLinkingService->linkInquiry($inquiry, $entry['lines'])) {
                $linkedCount++;
            } else {
                $blockedCount++;
            }
        }

        $status = "{$linkedCount}件を一括紐付けしました。";
        if ($blockedCount > 0) {
            $status .= "{$blockedCount}件はすでに紐付け済みのためスキップしました。";
        }

        return redirect()->route('admin.product-monitor-links.index')->with('status', $status);
    }

    /**
     * @return array{matched: array<int, array{name: string, inquiry: Inquiry, lines: array<int, array{tsunagu_unit_price: int, agency_unit_price: int, count: int, memo: ?string}>, isA01: bool}>, a01: array<int, array{name: string, lines: array<int, array{tsunagu_unit_price: int, agency_unit_price: int, count: int, memo: ?string}>}>}
     */
    private function parseBulkRows(string $text): array
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $text);
        rewind($handle);

        $matched = [];
        $a01 = [];
        $claimedIds = [];

        while (($row = fgetcsv($handle, 0, "\t", '"')) !== false) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                continue;
            }

            $productsA = trim($row[2] ?? '');
            $qtyA = (int) trim($row[3] ?? '');
            $productsB = trim($row[4] ?? '');
            $qtyB = (int) trim($row[5] ?? '');

            $lines = [];
            if ($qtyA > 0) {
                $lines[] = [
                    'tsunagu_unit_price' => 1000,
                    'agency_unit_price' => 500,
                    'count' => $qtyA,
                    'memo' => $productsA !== '' ? str_replace("\n", '、', $productsA) : null,
                ];
            }
            if ($qtyB > 0) {
                $lines[] = [
                    'tsunagu_unit_price' => 500,
                    'agency_unit_price' => 0,
                    'count' => $qtyB,
                    'memo' => $productsB !== '' ? str_replace("\n", '、', $productsB) : null,
                ];
            }

            if (empty($lines)) {
                continue;
            }

            $inquiry = Inquiry::with('agency')
                ->where('project_id', self::TARGET_PROJECT_ID)
                ->where('name', $name)
                ->where(function ($q) {
                    $q->whereDoesntHave('contracts')
                        ->orWhereHas('project', fn ($q2) => $q2->where('is_recurring', true));
                })
                ->orderBy('inquired_at')
                ->get()
                ->first(fn (Inquiry $c) => ! in_array($c->id, $claimedIds, true));

            // A01(シェアポイ)扱い(既存の紐付け先がA01の場合も含む)はパートナー単価をTSUNAGU単価と同額(満額)にする
            $isA01 = ! $inquiry || $inquiry->agency?->legacy_code === 'A01';

            $finalLines = $isA01
                ? array_map(fn (array $line) => [...$line, 'agency_unit_price' => $line['tsunagu_unit_price']], $lines)
                : $lines;

            if ($inquiry) {
                $claimedIds[] = $inquiry->id;

                $matched[] = [
                    'name' => $name,
                    'inquiry' => $inquiry,
                    'lines' => $finalLines,
                    'isA01' => $isA01,
                ];

                continue;
            }

            $a01[] = [
                'name' => $name,
                'lines' => $finalLines,
            ];
        }

        fclose($handle);

        return ['matched' => $matched, 'a01' => $a01];
    }
}
