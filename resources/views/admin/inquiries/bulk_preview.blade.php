@extends('layouts.admin')

@section('title', '問い合わせ一括追加プレビュー')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">問い合わせ一括追加プレビュー</h1>
    <a href="{{ route('admin.inquiries.index') }}" class="text-sm text-blue-600 hover:underline">戻る</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <p class="text-sm text-gray-700">
        追加対象: <span class="font-semibold">{{ count($valid) }}件</span>
        エラー: <span class="font-semibold text-red-600">{{ count($invalid) }}件</span>
    </p>
    <p class="text-xs text-gray-500 mt-1">内容を確認のうえ、問題なければ下の「この内容で一括追加する」を押してください。エラーの行は追加されません。</p>
</div>

@if (count($valid) > 0)
    <h2 class="text-sm font-medium text-gray-700 mb-3">追加される行</h2>
    <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto mb-6">
        <table class="w-full text-sm min-w-max">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">タイムスタンプ</th>
                    <th class="px-4 py-3 font-medium">紹介パートナー</th>
                    <th class="px-4 py-3 font-medium">案件名</th>
                    <th class="px-4 py-3 font-medium">LINE名</th>
                    <th class="px-4 py-3 font-medium">お名前</th>
                    <th class="px-4 py-3 font-medium">フリガナ</th>
                    <th class="px-4 py-3 font-medium">メールアドレス</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($valid as $row)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row['timestamp']?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $row['agency']->name }}（{{ $row['agency']->referral_code }}）</td>
                        <td class="px-4 py-3">{{ $row['project']->name }}</td>
                        <td class="px-4 py-3">{{ $row['line_display_name'] ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $row['name'] }}</td>
                        <td class="px-4 py-3">{{ $row['name_kana'] }}</td>
                        <td class="px-4 py-3">{{ $row['email'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($invalid) > 0)
    <h2 class="text-sm font-medium text-gray-700 mb-3">エラーのため追加されない行</h2>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">貼り付けた行</th>
                    <th class="px-4 py-3 font-medium">理由</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($invalid as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $row['raw'] }}</td>
                        <td class="px-4 py-3 text-red-600">{{ implode('、', $row['errors']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($valid) > 0)
    <form method="POST" action="{{ route('admin.inquiries.bulk-store') }}" onsubmit="return confirm('{{ count($valid) }}件の問い合わせを追加します。よろしいですか？');">
        @csrf
        <input type="hidden" name="pasted_text" value="{{ $pastedText }}">
        <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">この内容で一括追加する</button>
    </form>
@endif
@endsection
