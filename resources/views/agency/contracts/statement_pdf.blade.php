<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<style>
    @font-face {
        font-family: 'NotoSansJP';
        font-weight: normal;
        src: url('{{ resource_path('fonts/NotoSansJP-Regular.ttf') }}');
    }
    @font-face {
        font-family: 'NotoSansJP';
        font-weight: bold;
        src: url('{{ resource_path('fonts/NotoSansJP-Bold.ttf') }}');
    }
    @page { margin: 30px 40px; }
    body { font-family: 'NotoSansJP', sans-serif; font-size: 11px; color: #1f2937; }
    h1 { text-align: center; font-size: 20px; letter-spacing: 8px; margin-bottom: 24px; }
    .header { display: flex; justify-content: space-between; margin-bottom: 16px; }
    .header .right { text-align: right; }
    .amount-box { border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 10px 0; margin: 16px 0; }
    .amount-box .amount { font-size: 22px; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    th { background: #f9fafb; color: #6b7280; font-weight: normal; }
    .section-title { font-weight: bold; margin: 16px 0 4px; }
    .text-right { text-align: right; }
</style>
</head>
<body>
    <h1>支払通知書</h1>

    <div class="header">
        <div>
            <p>{{ $agency->company_name ?: $agency->name }} 様</p>
        </div>
        <div class="right">
            <p>発行日：{{ $issuedAt->format('Y/m/d') }}</p>
            <p>支払通知番号：{{ $statementNumber }}</p>
            <p>集計期間：{{ \Illuminate\Support\Carbon::parse($month.'-01')->format('Y/m/d') }}〜{{ \Illuminate\Support\Carbon::parse($month.'-01')->endOfMonth()->format('Y/m/d') }}</p>
        </div>
    </div>

    <p>摘要：{{ $month }}分</p>
    <p>振込予定日：{{ \Illuminate\Support\Carbon::parse($month.'-01')->addMonthNoOverflow()->day(5)->format('Y/m/d') }}</p>

    <div class="amount-box">
        報酬金額　<span class="amount">¥{{ number_format($monthlyTotal) }}</span>（税込）
    </div>

    <div class="section-title">パートナー報酬</div>
    <table>
        <thead>
            <tr>
                <th>着金日</th>
                <th>案件名</th>
                <th class="text-right">金額</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contracts as $contract)
                <tr>
                    <td>{{ $contract->deposit_date->format('Y/m/d') }}</td>
                    <td>{{ $contract->inquiry->project->name }}</td>
                    <td class="text-right">¥{{ number_format($contract->agency_reward_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">対象データはありません。</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">紹介報酬10%</div>
    <table>
        <thead>
            <tr>
                <th>紹介先パートナー</th>
                <th>着金数</th>
                <th class="text-right">金額</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($referralCommissionGroups as $group)
                <tr>
                    <td>{{ $group['sourceAgency']->name }}</td>
                    <td>{{ $group['count'] }}</td>
                    <td class="text-right">¥{{ number_format($group['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">対象データはありません。</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">共創パートナー30%</div>
    <table>
        <thead>
            <tr>
                <th>取引先名</th>
                <th>着金数</th>
                <th class="text-right">金額</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($collaborationRewardRows as $row)
                <tr>
                    <td>{{ $row['clientName'] }}</td>
                    <td>{{ $row['depositCount'] }}</td>
                    <td class="text-right">¥{{ number_format($row['rewardAmount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">対象データはありません。</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
