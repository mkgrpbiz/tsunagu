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

class MysteryShopperLinkController extends Controller
{
    private const TARGET_PROJECT_ID = 8;

    public function __construct(private readonly ContractLinkingService $contractLinkingService)
    {
    }

    public function index(): View
    {
        return view('admin.mystery_shopper_links.index');
    }

    public function bulkPreview(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkRows($data['pasted_text']);

        return view('admin.mystery_shopper_links.bulk_preview', [
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

        return redirect()->route('admin.mystery-shopper-links.index')->with('status', $status);
    }

    /**
     * @return array{matched: array<int, array{name: string, inquiry: Inquiry, lines: array<int, array{tsunagu_unit_price: int, agency_unit_price: int, count: int, memo: ?string}>}>, a01: array<int, array{name: string, lines: array<int, array{tsunagu_unit_price: int, agency_unit_price: int, count: int, memo: ?string}>}>}
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
            $count = (int) preg_replace('/[^\d]/', '', trim($row[2] ?? ''));

            if ($name === '' || $count <= 0) {
                continue;
            }

            $itemLines = [[
                'tsunagu_unit_price' => $count * 1000,
                'agency_unit_price' => $count * 500,
                'count' => 1,
                'memo' => null,
            ]];

            $inquiry = Inquiry::where('project_id', self::TARGET_PROJECT_ID)
                ->where('name', $name)
                ->where(function ($q) {
                    $q->whereDoesntHave('contracts')
                        ->orWhereHas('project', fn ($q2) => $q2->where('is_recurring', true));
                })
                ->orderBy('inquired_at')
                ->get()
                ->first(fn (Inquiry $c) => ! in_array($c->id, $claimedIds, true));

            if ($inquiry) {
                $claimedIds[] = $inquiry->id;

                $matched[] = [
                    'name' => $name,
                    'inquiry' => $inquiry,
                    'lines' => $itemLines,
                ];

                continue;
            }

            $a01Lines = array_map(fn (array $line) => [
                ...$line,
                'agency_unit_price' => $line['tsunagu_unit_price'],
            ], $itemLines);

            $a01[] = [
                'name' => $name,
                'lines' => $a01Lines,
            ];
        }

        fclose($handle);

        return ['matched' => $matched, 'a01' => $a01];
    }
}
