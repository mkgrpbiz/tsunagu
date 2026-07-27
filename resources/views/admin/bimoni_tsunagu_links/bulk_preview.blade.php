@extends('layouts.admin')

@section('title', 'BIMONI(TSUNAGU) - 一括紐付けプレビュー')

@section('content')
<h1 class="text-xl font-semibold mb-6">一括紐付けプレビュー</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-sm text-gray-700 mb-1">マッチ: {{ collect($matched)->sum(fn ($m) => count($m['rows'])) }}件（{{ count($matched) }}名）</p>
    @if (count($unmatched) > 0)
        <p class="text-sm text-red-600">非マッチ: {{ count($unmatched) }}件</p>
    @endif
</div>

@if (count($matched) > 0)
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-2 font-medium">名前</th>
                    <th class="text-left px-4 py-2 font-medium">フリガナ</th>
                    <th class="text-left px-4 py-2 font-medium">商品名(メモ)</th>
                    <th class="text-left px-4 py-2 font-medium">案件</th>
                    <th class="text-left px-4 py-2 font-medium">パートナー</th>
                    <th class="text-right px-4 py-2 font-medium">TSUNAGU単価</th>
                    <th class="text-right px-4 py-2 font-medium">パートナー単価</th>
                    <th class="text-right px-4 py-2 font-medium">件数</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($matched as $match)
                    @foreach ($match['rows'] as $i => $row)
                        <tr>
                            <td class="px-4 py-2">{{ $i === 0 ? $match['inquiry']->name : '' }}</td>
                            <td class="px-4 py-2">{{ $i === 0 ? $match['inquiry']->name_kana : '' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $row['memo'] }}</td>
                            <td class="px-4 py-2">{{ $i === 0 ? $match['inquiry']->project?->name : '' }}</td>
                            <td class="px-4 py-2">{{ $i === 0 ? $match['inquiry']->agency?->name : '' }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($row['tsunagu_price']) }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($row['agency_price']) }}</td>
                            <td class="px-4 py-2 text-right">{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($unmatched) > 0)
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-sm font-medium text-red-700 mb-2">非マッチ行</p>
        <ul class="text-xs text-red-600 space-y-1">
            @foreach ($unmatched as $line)
                <li>{{ $line['raw'] }} — {{ $line['reason'] }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.bimoni-tsunagu-links.bulk-store') }}" class="flex gap-3">
    @csrf
    <textarea name="pasted_text" hidden>{{ $pastedText }}</textarea>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2" @disabled(count($matched) === 0)>
        確定して着金処理
    </button>
    <a href="{{ route('admin.bimoni-tsunagu-links.index') }}" class="text-sm text-gray-500 px-4 py-2">戻る</a>
</form>
@endsection
