@extends('layouts.admin')

@section('title', 'SharePoy+報告管理')

@section('content')
<h1 class="text-xl font-semibold mb-6">SharePoy+報告管理</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <form method="GET" action="{{ route('admin.sharepoy-reports.index') }}" class="flex gap-2 items-end">
        <div class="flex-1">
            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">SharePoy+ユーザーID</label>
            <input type="text" name="user_id" id="user_id" value="{{ $userId }}"
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">検索</button>
    </form>
</div>

@if ($userId !== '')
    @if (! $sharePoyUser)
        <p class="text-sm text-gray-400">該当するユーザーが見つかりません。</p>
    @else
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
            <p class="text-sm text-gray-700">{{ $sharePoyUser->name }}（{{ $sharePoyUser->name_kana }}）— ユーザーID: {{ $sharePoyUser->sharepoy_user_id }}</p>
        </div>

        <h2 class="text-sm font-medium text-gray-700 mb-3">着金履歴</h2>
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium">着金日</th>
                        <th class="text-left px-4 py-2 font-medium">区分</th>
                        <th class="text-left px-4 py-2 font-medium">問い合わせ</th>
                        <th class="text-right px-4 py-2 font-medium">TSUNAGU単価</th>
                        <th class="text-right px-4 py-2 font-medium">パートナー単価</th>
                        <th class="text-right px-4 py-2 font-medium">件数</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($depositRecords as $record)
                        <tr>
                            <td class="px-4 py-2">{{ $record->deposit_date->format('Y/m/d') }}</td>
                            <td class="px-4 py-2">{{ $record->source }}</td>
                            <td class="px-4 py-2">{{ $record->inquiry?->name ?? '-' }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($record->tsunagu_unit_price) }}</td>
                            <td class="px-4 py-2 text-right">¥{{ number_format($record->agency_unit_price) }}</td>
                            <td class="px-4 py-2 text-right">{{ $record->count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400">まだ着金履歴がありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endif
@endsection
