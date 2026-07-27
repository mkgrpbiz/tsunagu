<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Contract;
use App\Models\SharePoyDepositRecord;
use App\Models\SharePoyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SharePoyPointController extends Controller
{
    private const POINTS_PER_LINE = 300;

    private const PRODUCT_MONITOR_PROJECT_ID = 3;

    private const MYSTERY_SHOPPER_PROJECT_ID = 8;

    public function index(): View
    {
        return view('admin.sharepoy_points.index', [
            'productMonitor' => $this->summarize(self::PRODUCT_MONITOR_PROJECT_ID, '商品受け取りモニター紹介', true),
            'mysteryShopper' => $this->summarize(self::MYSTERY_SHOPPER_PROJECT_ID, '覆面調査モニター紹介', false),
        ]);
    }

    public function storeProductMonitor(): RedirectResponse
    {
        $count = $this->store(self::PRODUCT_MONITOR_PROJECT_ID, 'product_monitor', true);

        return redirect()->route('admin.sharepoy-points.index')->with('status', "商品受け取りモニター: {$count}件をSharePoy+ユーザーの着金履歴に記録しました。");
    }

    public function storeMysteryShopper(): RedirectResponse
    {
        $count = $this->store(self::MYSTERY_SHOPPER_PROJECT_ID, 'mystery_shopper', false);

        return redirect()->route('admin.sharepoy-points.index')->with('status', "覆面調査モニター: {$count}件をSharePoy+ユーザーの着金履歴に記録しました。");
    }

    /**
     * @return array{groups: array<int, array{sharePoyUser: SharePoyUser, name: string, count: int, points: int, contracts: Collection<int, Contract>}>, unmatched: array<int, array{name: string, count: int, contracts: Collection<int, Contract>}>, copyText: string, label: string}
     */
    private function summarize(int $projectId, string $label, bool $onlyThousandYenLines): array
    {
        $contracts = $this->unprocessedContracts($projectId, $onlyThousandYenLines);

        $byName = $contracts->groupBy(fn (Contract $c) => $c->inquiry->name);

        $groups = [];
        $unmatched = [];

        foreach ($byName as $name => $group) {
            $sharePoyUser = SharePoyUser::where('name', $name)->first();
            $totalCount = $group->sum('count');

            if (! $sharePoyUser) {
                $unmatched[] = ['name' => $name, 'count' => $totalCount, 'contracts' => $group];

                continue;
            }

            $groups[] = [
                'sharePoyUser' => $sharePoyUser,
                'name' => $name,
                'count' => $totalCount,
                'points' => $totalCount * self::POINTS_PER_LINE,
                'contracts' => $group,
            ];
        }

        $copyText = collect($groups)
            ->map(fn (array $g) => implode("\t", [$g['sharePoyUser']->sharepoy_user_id, $g['name'], $g['points'], $label]))
            ->implode("\n");

        return ['groups' => $groups, 'unmatched' => $unmatched, 'copyText' => $copyText, 'label' => $label];
    }

    private function store(int $projectId, string $source, bool $onlyThousandYenLines): int
    {
        $result = $this->summarize($projectId, '', $onlyThousandYenLines);

        $savedCount = 0;

        foreach ($result['groups'] as $group) {
            foreach ($group['contracts'] as $contract) {
                SharePoyDepositRecord::create([
                    'sharepoy_user_id' => $group['sharePoyUser']->id,
                    'inquiry_id' => $contract->inquiry_id,
                    'contract_id' => $contract->id,
                    'source' => $source,
                    'deposit_date' => $contract->deposit_date,
                    'tsunagu_unit_price' => (int) ($contract->count > 0 ? $contract->deposit_amount / $contract->count : 0),
                    'agency_unit_price' => 0,
                    'count' => $contract->count,
                    'memo' => null,
                ]);
                $savedCount++;
            }
        }

        return $savedCount;
    }

    /**
     * @return Collection<int, Contract>
     */
    private function unprocessedContracts(int $projectId, bool $onlyThousandYenLines): Collection
    {
        $a01AgencyId = Agency::where('legacy_code', 'A01')->value('id');

        return Contract::with('inquiry')
            ->whereHas('inquiry', fn ($q) => $q->where('project_id', $projectId)->where('agency_id', $a01AgencyId))
            ->whereDoesntHave('sharePoyDepositRecord')
            ->get()
            ->filter(fn (Contract $c) => ! $onlyThousandYenLines || ($c->count > 0 && $c->deposit_amount === $c->count * 1000))
            ->values();
    }
}
