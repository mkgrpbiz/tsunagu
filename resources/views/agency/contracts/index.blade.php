@extends('layouts.agency')

@section('title', '着金・支払い')

@section('content')
<h1 class="text-xl font-semibold mb-6">着金・支払い</h1>

<div class="bg-blue-50 border border-blue-100 rounded-lg p-6 mb-6 text-sm text-gray-700 leading-relaxed">
    <p class="font-semibold mb-1">お支払いについて</p>
    <p>TSUNAGU着金確認後、月末で締めて翌月5日にお振込み</p>
    <p>※金額1,000円未満の場合、翌月に繰り越されます。</p>
</div>

<form method="GET" action="{{ route('agency.contracts.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label for="month" class="block text-xs font-medium text-gray-700 mb-1">月で絞り込み</label>
        <div class="flex gap-2">
            <select name="month" id="month" onchange="this.form.submit()" class="rounded-md border border-gray-300 text-sm">
                <option value="" disabled @selected(! $month)>月を選択</option>
                @foreach ($months as $ym)
                    <option value="{{ $ym }}" @selected($month === $ym)>{{ $ym }}</option>
                @endforeach
            </select>
            <button type="submit" name="month" value="all" class="text-sm font-medium rounded-md px-3 {{ ! $month ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">累計</button>
        </div>
    </div>
</form>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-sm text-gray-500">パートナー報酬合計</p>
    <p class="text-lg font-semibold mt-1">{{ $month ?: '全期間' }}：¥{{ number_format($monthlyTotal) }}</p>
    <div class="text-sm text-gray-600 mt-3 space-y-1">
        <p>紹介報酬：¥{{ number_format($monthlyPayoutTotal) }}</p>
        <p>パートナー10%：¥{{ number_format($monthlyReferralTotal) }}</p>
        <p>共創パートナー30%：¥{{ number_format($monthlyCollaborationRewardTotal) }}</p>
        <p>繰り越し報酬：¥{{ number_format($carryOverAmount) }}</p>
    </div>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">紹介報酬{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-x-auto mb-8">
    <table class="w-full text-sm min-w-max">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">案件名</th>
                <th class="px-4 py-3 font-medium">名前</th>
                <th class="px-4 py-3 font-medium">フリガナ</th>
                <th class="px-4 py-3 font-medium">紹介報酬</th>
                <th class="px-4 py-3 font-medium">着金日</th>
                <th class="px-4 py-3 font-medium">支払予定日</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($contracts as $contract)
                <tr>
                    <td class="px-4 py-3">{{ $contract->inquiry->project->name }}</td>
                    <td class="px-4 py-3">{{ $contract->inquiry->name }}</td>
                    <td class="px-4 py-3">{{ $contract->inquiry->name_kana }}</td>
                    <td class="px-4 py-3">¥{{ number_format($contract->agency_reward_amount) }}</td>
                    <td class="px-4 py-3">{{ $contract->deposit_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">{{ $contract->payment_due_date->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">着金はまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">パートナー10%{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
    <table class="w-full text-sm min-w-max">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">紹介先パートナー</th>
                <th class="px-4 py-3 font-medium">着金数</th>
                <th class="px-4 py-3 font-medium">紹介報酬10%</th>
                <th class="px-4 py-3 font-medium">支払予定日</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($referralCommissionGroups as $group)
                <tr>
                    <td class="px-4 py-3">{{ $group['sourceAgency']->name }}</td>
                    <td class="px-4 py-3">{{ $group['count'] }}</td>
                    <td class="px-4 py-3">¥{{ number_format($group['total']) }}</td>
                    <td class="px-4 py-3">{{ $group['paymentDueDate']->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">紹介報酬はまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3 mt-8">共創パートナー30%{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
    <table class="w-full text-sm min-w-max">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">取引先名</th>
                <th class="px-4 py-3 font-medium">案件数</th>
                <th class="px-4 py-3 font-medium">着金数</th>
                <th class="px-4 py-3 font-medium">紹介報酬30%</th>
                <th class="px-4 py-3 font-medium">支払予定日</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($collaborationRewardRows as $row)
                <tr>
                    <td class="px-4 py-3">{{ $row['clientName'] }}</td>
                    <td class="px-4 py-3">{{ $row['projectCount'] }}</td>
                    <td class="px-4 py-3">{{ $row['depositCount'] }}</td>
                    <td class="px-4 py-3">¥{{ number_format($row['rewardAmount']) }}</td>
                    <td class="px-4 py-3">{{ $row['paymentDueDate']->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">共創報酬はまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
