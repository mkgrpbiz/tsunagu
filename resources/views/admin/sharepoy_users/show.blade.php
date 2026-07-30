@extends('layouts.admin')

@section('title', 'SharePoy+管理 - ' . $sharepoyUser->name)

@section('content')
<h1 class="text-xl font-semibold mb-6">{{ $sharepoyUser->name }}（{{ $sharepoyUser->name_kana }}）</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6 grid grid-cols-2 gap-4 text-sm">
    <div>
        <span class="block text-gray-500 mb-1">ユーザーID</span>
        <span class="font-medium">{{ $sharepoyUser->sharepoy_user_id }}</span>
    </div>
    <div>
        <span class="block text-gray-500 mb-1">紹介者ID</span>
        <span class="font-medium">{{ $sharepoyUser->referrer_sharepoy_user_id ?? '-' }}</span>
    </div>
</div>

<h2 class="text-sm font-medium text-gray-700 mb-3">TSUNAGU着金履歴</h2>
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
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td class="px-4 py-2">{{ $record->deposit_date->format('Y/m/d') }}</td>
                    <td class="px-4 py-2">{{ $record->sourceLabel() }}</td>
                    <td class="px-4 py-2">{{ $record->inquiry?->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">¥{{ number_format($record->tsunagu_unit_price) }}</td>
                    <td class="px-4 py-2 text-right">¥{{ number_format($record->agency_unit_price) }}</td>
                    <td class="px-4 py-2 text-right">{{ $record->count }}</td>
                </tr>
            @empty
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">まだ着金履歴がありません</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<a href="{{ route('admin.sharepoy-users.index') }}" class="inline-block mt-6 text-sm text-gray-500">一覧に戻る</a>
@endsection
