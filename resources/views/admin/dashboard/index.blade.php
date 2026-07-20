@extends('layouts.admin')

@section('title', 'ダッシュボード')

@section('content')
<h1 class="text-xl font-semibold mb-6">ダッシュボード</h1>

<form method="GET" action="{{ route('admin.dashboard') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
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

@php
    $cards = [
        ['label' => 'パートナー数', 'data' => $summary['referral_partners'], 'unit' => '件'],
        ['label' => '共創パートナー数', 'data' => $summary['collaboration_partners'], 'unit' => '件'],
        ['label' => '問い合わせ数', 'data' => $summary['inquiries'], 'unit' => '件'],
        ['label' => '着金数', 'data' => $summary['deposits'], 'unit' => '件'],
        ['label' => '売上', 'data' => $summary['revenue'], 'unit' => '円'],
        ['label' => '支払い', 'data' => $summary['payout'], 'unit' => '円'],
        ['label' => '利益', 'data' => $summary['profit'], 'unit' => '円'],
        ['label' => '繰り越し予定合計', 'data' => ['monthly' => $carryOverTotal, 'compare' => null], 'unit' => '円', 'note' => '累計未払いが¥1,000未満のパートナー分'],
    ];
@endphp

<div class="grid md:grid-cols-4 gap-4 mb-8">
    @foreach ($cards as $card)
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
            <p class="text-xl font-semibold mt-1">
                {{ $card['unit'] === '円' ? '¥'.number_format($card['data']['monthly']) : number_format($card['data']['monthly']).$card['unit'] }}
            </p>
            @if ($card['data']['compare'])
                @php $diff = $card['data']['compare']['diff']; $percent = $card['data']['compare']['percent']; @endphp
                <p class="text-xs mt-1 {{ $diff > 0 ? 'text-green-600' : ($diff < 0 ? 'text-red-600' : 'text-gray-500') }}">
                    前月比:
                    {{ $diff > 0 ? '+' : '' }}{{ $card['unit'] === '円' ? '¥'.number_format($diff) : number_format($diff).$card['unit'] }}
                    @if ($percent !== null)
                        （{{ $percent > 0 ? '+' : '' }}{{ $percent }}%）
                    @endif
                </p>
            @else
                <p class="text-xs text-gray-500 mt-1">
                    {{ $card['note'] ?? '累計: '.($card['unit'] === '円' ? '¥'.number_format($card['data']['cumulative']) : number_format($card['data']['cumulative']).$card['unit']) }}
                </p>
            @endif
        </div>
    @endforeach
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">パートナー数・問い合わせ数（直近12ヶ月）</h2>
        @include('partials.line_chart', [
            'points' => $chartData,
            'series' => [
                ['key' => 'referral_partners', 'label' => 'パートナー数', 'color' => '#2563eb'],
                ['key' => 'inquiries', 'label' => '問い合わせ数', 'color' => '#f59e0b'],
            ],
        ])
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">売上・利益（直近12ヶ月）</h2>
        @include('partials.line_chart', [
            'points' => $chartData,
            'series' => [
                ['key' => 'revenue', 'label' => '売上', 'color' => '#2563eb'],
                ['key' => 'profit', 'label' => '利益', 'color' => '#16a34a'],
            ],
            'unit' => '¥',
        ])
    </div>
</div>
@endsection
