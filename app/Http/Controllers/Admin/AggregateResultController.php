<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Inquiry;
use App\Models\Project;
use App\Services\ContractLinkingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AggregateResultController extends Controller
{
    public function __construct(private readonly ContractLinkingService $contractLinkingService)
    {
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $candidates = collect();

        if ($q !== '') {
            // referral_code(会員番号)はlegacy_codeベースの算出プロパティのため、DBクエリではなくPHP側でフィルタする
            $candidates = Agency::where('is_system', false)
                ->orderBy('name')
                ->get()
                ->filter(fn (Agency $agency) => str_contains($agency->referral_code, $q)
                    || str_contains((string) $agency->name, $q)
                    || str_contains((string) $agency->name_kana, $q)
                    || str_contains((string) $agency->line_display_name, $q))
                ->values();
        }

        $agencyId = $request->query('agency_id');
        $selectedAgency = $agencyId ? Agency::where('is_system', false)->find($agencyId) : null;

        $history = collect();
        if ($selectedAgency) {
            $history = Inquiry::where('agency_id', $selectedAgency->id)
                ->where('name', '合計成果反映')
                ->with(['project', 'contract.referralCommission'])
                ->latest('created_at')
                ->get();
        }

        return view('admin.aggregate_results.index', [
            'q' => $q,
            'candidates' => $candidates,
            'selectedAgency' => $selectedAgency,
            'projects' => Project::orderBy('name')->get(),
            'history' => $history,
        ]);
    }

    public function store(Request $request, Agency $agency): RedirectResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.project_id' => ['required', 'exists:projects,id'],
            'lines.*.tsunagu_unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.agency_unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.count' => ['required', 'integer', 'min:1'],
            'lines.*.apply_referral_commission' => ['nullable', 'boolean'],
        ]);

        foreach ($data['lines'] as $line) {
            $inquiry = Inquiry::create([
                'agency_id' => $agency->id,
                'project_id' => $line['project_id'],
                'name' => '合計成果反映',
                'name_kana' => 'ゴウケイセイカハンエイ',
                'email' => '',
                'status' => InquiryStatus::Contracted,
                'inquired_at' => now(),
                'is_legacy_import' => false,
            ]);

            $this->contractLinkingService->linkInquiry($inquiry, [[
                'tsunagu_unit_price' => $line['tsunagu_unit_price'],
                'agency_unit_price' => $line['agency_unit_price'],
                'count' => $line['count'],
                'apply_referral_commission' => $line['apply_referral_commission'] ?? true,
            ]]);
        }

        return redirect()
            ->route('admin.aggregate-results.index', ['agency_id' => $agency->id])
            ->with('status', count($data['lines']).'件の成果を反映しました。');
    }
}
