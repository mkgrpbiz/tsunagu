@extends('layouts.admin')

@section('title', '商品受け取りモニター - 一括紐付けプレビュー')

@section('content')
<h1 class="text-xl font-semibold mb-6">一括紐付けプレビュー</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-sm text-gray-700 mb-1">マッチ: {{ count($matched) }}件</p>
    <p class="text-sm text-amber-600">A01(シェアポイ)処理: {{ count($a01) }}件</p>
</div>

@if (count($matched) > 0)
    <h2 class="text-sm font-medium text-gray-700 mb-2">マッチ一覧</h2>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-2 font-medium">名前</th>
                    <th class="text-left px-4 py-2 font-medium">パートナー</th>
                    <th class="text-left px-4 py-2 font-medium">商品名</th>
                    <th class="text-right px-4 py-2 font-medium">単価(TSUNAGU/パートナー)</th>
                    <th class="text-right px-4 py-2 font-medium">数量</th>
                    <th class="text-right px-4 py-2 font-medium">TSUNAGU合計</th>
                    <th class="text-right px-4 py-2 font-medium">パートナー合計</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($matched as $item)
                    @foreach ($item['lines'] as $i => $line)
                        <tr class="even:bg-gray-50 hover:bg-gray-100">
                            <td class="px-4 py-2">{{ $i === 0 ? $item['name'] : '' }}</td>
                            <td class="px-4 py-2">
                                @if ($i === 0)
                                    {{ $item['inquiry']->agency?->name }}
                                    @if ($item['isA01'])
                                        <span class="text-amber-600 text-xs">(A01・満額)</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-500">{{ $line['memo'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($line['tsunagu_unit_price']) }} / ¥{{ number_format($line['agency_unit_price']) }}</td>
                            <td class="px-4 py-2 text-right">{{ $line['count'] }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($line['tsunagu_unit_price'] * $line['count']) }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($line['agency_unit_price'] * $line['count']) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($a01) > 0)
    <h2 class="text-sm font-medium text-gray-700 mb-2">A01(シェアポイ)処理一覧</h2>
    <div class="bg-amber-50 border border-amber-100 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-amber-100/50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-2 font-medium">名前</th>
                    <th class="text-left px-4 py-2 font-medium">商品名</th>
                    <th class="text-right px-4 py-2 font-medium">単価(TSUNAGU/パートナー)</th>
                    <th class="text-right px-4 py-2 font-medium">数量</th>
                    <th class="text-right px-4 py-2 font-medium">TSUNAGU合計</th>
                    <th class="text-right px-4 py-2 font-medium">パートナー合計</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-amber-100">
                @foreach ($a01 as $item)
                    @foreach ($item['lines'] as $i => $line)
                        <tr class="even:bg-gray-50 hover:bg-gray-100">
                            <td class="px-4 py-2">{{ $i === 0 ? $item['name'] : '' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $line['memo'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($line['tsunagu_unit_price']) }} / ¥{{ number_format($line['agency_unit_price']) }}</td>
                            <td class="px-4 py-2 text-right">{{ $line['count'] }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($line['tsunagu_unit_price'] * $line['count']) }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($line['agency_unit_price'] * $line['count']) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<form method="POST" action="{{ route('admin.product-monitor-links.bulk-store') }}" class="flex gap-3">
    @csrf
    <textarea name="pasted_text" hidden>{{ $pastedText }}</textarea>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2" @disabled(count($matched) === 0 && count($a01) === 0)>
        確定して着金処理
    </button>
    <a href="{{ route('admin.product-monitor-links.index') }}" class="text-sm text-gray-500 px-4 py-2">戻る</a>
</form>
@endsection
