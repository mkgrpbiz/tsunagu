<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Contract;
use App\Models\SharePoyDepositRecord;
use App\Models\SharePoyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SharePoyDepositHistoryController extends Controller
{
    /**
     * BIMONI・商品受け取りモニター・覆面調査モニターは専用画面（BimoniSharePoyLinkController /
     * SharePoyPointController）で着金履歴に記録するため、このcatch-all画面の対象からは除外する。
     */
    private const EXCLUDED_PROJECT_IDS = [2, 3, 8];

    public function index(): View
    {
        return view('admin.sharepoy_deposit_history.index', [
            'contracts' => $this->unprocessedContracts(),
        ]);
    }

    public function preview(Request $request): View
    {
        $data = $request->validate([
            'contract_ids' => ['required', 'array', 'min:1'],
            'contract_ids.*' => ['integer', 'exists:contracts,id'],
            'label' => ['required', 'string', 'max:255'],
            'points_per_line' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->summarize($data['contract_ids'], $data['label'], $data['points_per_line']);

        return view('admin.sharepoy_deposit_history.confirm', [
            'contractIds' => $data['contract_ids'],
            'label' => $data['label'],
            'pointsPerLine' => $data['points_per_line'],
            'result' => $result,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contract_ids' => ['required', 'array', 'min:1'],
            'contract_ids.*' => ['integer', 'exists:contracts,id'],
            'label' => ['required', 'string', 'max:255'],
            'points_per_line' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->summarize($data['contract_ids'], $data['label'], $data['points_per_line']);

        $savedCount = 0;

        foreach ([...$result['groups'], ...$result['noReferrerCode']] as $group) {
            foreach ($group['contracts'] as $contract) {
                $this->recordContract($contract, $group['sharePoyUser']->id, null);
                $savedCount++;
            }
        }

        $unmatchedPlaceholderId = null;

        foreach ($result['unmatched'] as $entry) {
            $unmatchedPlaceholderId ??= SharePoyUser::unmatchedPlaceholder()->id;

            foreach ($entry['contracts'] as $contract) {
                $this->recordContract($contract, $unmatchedPlaceholderId, $entry['name']);
                $savedCount++;
            }
        }

        return redirect()->route('admin.sharepoy-deposit-history.index')->with('status', "{$savedCount}件をSharePoy+ユーザーの着金履歴に記録しました。");
    }

    /**
     * 紹介ポイント付与のコピー用一覧は作らず、選択分を着金履歴にのみ記録する。
     */
    public function storeHistoryOnly(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contract_ids' => ['required', 'array', 'min:1'],
            'contract_ids.*' => ['integer', 'exists:contracts,id'],
        ]);

        // ラベル・ポイントはコピー用一覧の生成にしか使わないため、この経路ではダミー値でよい
        $result = $this->summarize($data['contract_ids'], '', 1);

        $savedCount = 0;

        foreach ([...$result['groups'], ...$result['noReferrerCode']] as $group) {
            foreach ($group['contracts'] as $contract) {
                $this->recordContract($contract, $group['sharePoyUser']->id, null);
                $savedCount++;
            }
        }

        $unmatchedPlaceholderId = null;

        foreach ($result['unmatched'] as $entry) {
            $unmatchedPlaceholderId ??= SharePoyUser::unmatchedPlaceholder()->id;

            foreach ($entry['contracts'] as $contract) {
                $this->recordContract($contract, $unmatchedPlaceholderId, $entry['name']);
                $savedCount++;
            }
        }

        return redirect()->route('admin.sharepoy-deposit-history.index')->with('status', "{$savedCount}件を着金履歴のみ記録しました(紹介ポイントの付与はスキップしました)。");
    }

    /**
     * @param  array<int, int>  $contractIds
     * @return array{groups: array<int, array{sharePoyUser: SharePoyUser, name: string, count: int, points: int, contracts: Collection<int, Contract>}>, noReferrerCode: array<int, array{sharePoyUser: SharePoyUser, name: string, count: int, points: int, contracts: Collection<int, Contract>}>, unmatched: array<int, array{name: string, count: int, contracts: Collection<int, Contract>}>, copyText: string, label: string}
     */
    private function summarize(array $contractIds, string $label, int $pointsPerLine): array
    {
        $contracts = Contract::with('inquiry')->whereIn('id', $contractIds)->get();

        $byName = $contracts->groupBy(fn (Contract $c) => $c->inquiry->name);

        $groups = [];
        $noReferrerCode = [];
        $unmatched = [];

        foreach ($byName as $name => $group) {
            $sharePoyUser = SharePoyUser::where('name', $name)->first();
            $totalCount = $group->sum('count');

            if (! $sharePoyUser) {
                $unmatched[] = ['name' => $name, 'count' => $totalCount, 'contracts' => $group];

                continue;
            }

            $row = [
                'sharePoyUser' => $sharePoyUser,
                'name' => $name,
                'count' => $totalCount,
                'points' => $totalCount * $pointsPerLine,
                'contracts' => $group,
            ];

            if (blank($sharePoyUser->referrer_sharepoy_user_id)) {
                $noReferrerCode[] = $row;
            } else {
                $groups[] = $row;
            }
        }

        $copyText = collect($groups)
            ->map(fn (array $g) => implode("\t", [$g['sharePoyUser']->referrer_sharepoy_user_id, $g['points'], $label]))
            ->implode("\n");

        return ['groups' => $groups, 'noReferrerCode' => $noReferrerCode, 'unmatched' => $unmatched, 'copyText' => $copyText, 'label' => $label];
    }

    private function recordContract(Contract $contract, int $sharePoyUserId, ?string $memo): void
    {
        SharePoyDepositRecord::create([
            'sharepoy_user_id' => $sharePoyUserId,
            'inquiry_id' => $contract->inquiry_id,
            'contract_id' => $contract->id,
            'source' => 'other',
            'deposit_date' => $contract->deposit_date,
            'tsunagu_unit_price' => (int) ($contract->count > 0 ? $contract->deposit_amount / $contract->count : 0),
            'agency_unit_price' => 0,
            'count' => $contract->count,
            'memo' => $memo,
        ]);
    }

    /**
     * @return Collection<int, Contract>
     */
    private function unprocessedContracts(): Collection
    {
        $a01AgencyId = Agency::where('legacy_code', 'A01')->value('id');

        return Contract::with(['inquiry.project'])
            ->whereHas('inquiry', fn ($q) => $q->where('agency_id', $a01AgencyId)->whereNotIn('project_id', self::EXCLUDED_PROJECT_IDS))
            ->whereDoesntHave('sharePoyDepositRecord')
            ->orderByDesc('deposit_date')
            ->get();
    }
}
