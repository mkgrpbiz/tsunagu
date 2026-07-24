@extends('layouts.admin')

@section('title', $agency->name.' 様 - 社内処理の詳細')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">{{ $agency->name }} 様 - 社内処理の詳細</h1>
    <a href="{{ route('admin.internal-agencies.index') }}" class="text-sm text-blue-600 hover:underline">一覧に戻る</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
        <div><dt class="text-gray-500">名前</dt><dd>{{ $agency->name }}</dd></div>
        <div><dt class="text-gray-500">会員番号</dt><dd>{{ $agency->referral_code }}</dd></div>
    </dl>

    <form method="GET" action="{{ route('admin.internal-agencies.show', $agency) }}" class="mt-6 pt-4 border-t border-gray-100 flex flex-wrap gap-4 items-end">
        <div>
            <label for="month" class="block text-xs font-medium text-gray-700 mb-1">対象月で絞り込み</label>
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
        <p class="text-sm text-gray-700">
            {{ $month ? $month.'の社内処理合計' : '累計 社内処理合計' }}: <span class="font-semibold">¥{{ number_format($total) }}</span>
        </p>
    </form>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">パートナー本体（Contract）</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">対象案件</th>
                <th class="px-4 py-3 font-medium">対象者</th>
                <th class="px-4 py-3 font-medium">着金日</th>
                <th class="px-4 py-3 font-medium">報酬額</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($contracts as $contract)
                <tr>
                    <td class="px-4 py-3">{{ $contract->inquiry->project->name }}</td>
                    <td class="px-4 py-3">{{ $contract->inquiry->name }}</td>
                    <td class="px-4 py-3">{{ $contract->deposit_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">¥{{ number_format($contract->agency_reward_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">データがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">紹介報酬10%</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">紹介先パートナー</th>
                <th class="px-4 py-3 font-medium">報酬額</th>
                <th class="px-4 py-3 font-medium">対象月</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($commissions as $commission)
                <tr>
                    <td class="px-4 py-3">{{ $commission->sourceAgency->name }}</td>
                    <td class="px-4 py-3">¥{{ number_format($commission->amount) }}</td>
                    <td class="px-4 py-3">{{ optional($commission->contract?->deposit_date)->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-gray-400">データがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">共創パートナー30%</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">取引先名</th>
                <th class="px-4 py-3 font-medium">対象月</th>
                <th class="px-4 py-3 font-medium">報酬額</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($collaborationRewards as $reward)
                <tr>
                    <td class="px-4 py-3">{{ $reward->client_name }}</td>
                    <td class="px-4 py-3">{{ $reward->month->format('Y-m') }}</td>
                    <td class="px-4 py-3">¥{{ number_format($reward->reward_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-gray-400">データがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
