@extends('layouts.admin')

@section('title', '支払い管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">支払い管理</h1>
    <a href="{{ route('admin.notification-message-settings.payments.edit') }}" class="text-sm text-blue-600 hover:underline">LINE通知文章を設定</a>
</div>

<form method="GET" action="{{ route('admin.payments.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label for="month" class="block text-xs font-medium text-gray-700 mb-1">支払予定月で絞り込み</label>
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

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">{{ $month ? $month.'の支払い予定合計' : '全期間の支払い予定合計' }}</p>
        <p class="text-2xl font-semibold mt-1">¥{{ number_format($monthlyTotal) }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">累計未払い合計</p>
        <p class="text-2xl font-semibold mt-1">¥{{ number_format($cumulativeTotal) }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">繰り越し予定合計（累計¥1,000未満）</p>
        <p class="text-2xl font-semibold mt-1">¥{{ number_format($carryOverTotal) }}</p>
    </div>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">パートナー別支払い{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">パートナー</th>
                <th class="px-4 py-3 font-medium">会員番号</th>
                <th class="px-4 py-3 font-medium">紹介報酬</th>
                <th class="px-4 py-3 font-medium">パートナー10%</th>
                <th class="px-4 py-3 font-medium">共創パートナー30%</th>
                <th class="px-4 py-3 font-medium">合計</th>
                <th class="px-4 py-3 font-medium w-24"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($agencySummaries as $row)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $row['agency']->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['agency']->referral_code }}</td>
                    <td class="px-4 py-3">¥{{ number_format($row['contract_total']) }}</td>
                    <td class="px-4 py-3">¥{{ number_format($row['commission_total']) }}</td>
                    <td class="px-4 py-3">¥{{ number_format($row['reward_total']) }}</td>
                    <td class="px-4 py-3 font-semibold">¥{{ number_format($row['total']) }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.payments.show', $row['agency']) }}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">詳細</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">対象パートナーがいません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mt-8 mb-3">繰り越し予定</h2>
<p class="text-xs text-gray-500 mb-3">累計の未払い合計（紹介報酬＋パートナー10%＋共創パートナー30%）が¥1,000未満のパートナーです。支払予定には含まれず、翌月以降に繰り越されます。</p>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">パートナー</th>
                <th class="px-4 py-3 font-medium">繰り越し予定額</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($carryOverAgencies as $row)
                <tr>
                    <td class="px-4 py-3">{{ $row['agency']->name }}</td>
                    <td class="px-4 py-3">¥{{ number_format($row['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="px-4 py-6 text-center text-gray-400">繰り越し予定のパートナーはいません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
