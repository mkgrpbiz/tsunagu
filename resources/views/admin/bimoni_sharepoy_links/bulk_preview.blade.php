@extends('layouts.admin')

@section('title', 'BIMONI(SharePoy) - 一括紐付けプレビュー')

@section('content')
<h1 class="text-xl font-semibold mb-6">一括紐付けプレビュー</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-sm text-gray-700 mb-1">紹介コード: {{ count($groups) }}件</p>
    @if (count($unmatched) > 0)
        <p class="text-sm text-red-600">非マッチ: {{ count($unmatched) }}件</p>
    @endif
</div>

@if (count($groups) > 0)
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-medium text-gray-700">コピー用一覧（紹介コード・ポイント・ラベル）</h2>
            <button type="button" onclick="copyToClipboard({{ Illuminate\Support\Js::from($copyText) }})"
                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md px-3 py-1.5">コピー</button>
        </div>
        <textarea readonly rows="{{ count($groups) }}" class="w-full rounded-md border border-gray-300 font-mono text-xs bg-gray-50">{{ $copyText }}</textarea>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-2 font-medium">紹介コード</th>
                    <th class="text-left px-4 py-2 font-medium">名前</th>
                    <th class="text-left px-4 py-2 font-medium">フリガナ</th>
                    <th class="text-left px-4 py-2 font-medium">SharePoy+ユーザー</th>
                    <th class="text-right px-4 py-2 font-medium">件数</th>
                    <th class="text-right px-4 py-2 font-medium">ポイント</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($groups as $group)
                    <tr class="even:bg-gray-50 hover:bg-gray-100">
                        <td class="px-4 py-2">{{ $group['code'] }}</td>
                        <td class="px-4 py-2">{{ $group['name'] }}</td>
                        <td class="px-4 py-2">{{ $group['nameKana'] }}</td>
                        <td class="px-4 py-2">
                            @if ($group['sharePoyUser'])
                                <a href="{{ route('admin.sharepoy-users.show', $group['sharePoyUser']) }}" class="text-blue-600 hover:underline">紐付け済み</a>
                            @else
                                <span class="text-gray-400">SPコード直接指定</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">{{ $group['count'] }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($group['points']) }}pt</td>
                    </tr>
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

<div class="border-t border-gray-200 pt-6 mt-2">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">TSUNAGU側 一括着金紐付け（A01 / シェアポイ）</h2>
    <p class="text-xs text-gray-500 mb-3">
        SharePoy+ユーザーとの紐付け有無に関わらず、金額が読み取れた行はすべて対象です。金額ごとに件数をまとめて、A01（シェアポイ）名義でBIMONI【募集モニター30件以上】に一括で着金紐付けします。
    </p>

    @if (count($amountGroups) > 0)
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium">内容</th>
                        <th class="text-right px-4 py-2 font-medium">件数</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($amountGroups as $group)
                        <tr class="even:bg-gray-50">
                            <td class="px-4 py-2">BIMONI　{{ number_format($group['amount']) }}円</td>
                            <td class="px-4 py-2 text-right">{{ $group['count'] }}件</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-gray-400 mb-4">金額が読み取れる行がありません。</p>
    @endif

    @if (count($noAmount) > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-amber-700 mb-2">金額が読み取れなかった行（着金紐付けの対象外）</p>
            <ul class="text-xs text-amber-700 space-y-1">
                @foreach ($noAmount as $line)
                    <li>{{ $line['raw'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <p class="text-xs text-gray-500 mb-3">
        「SharePoy+ユーザーの着金履歴への記録」と「A01への一括着金紐付け」は1つの操作としてまとめて実行します。次の画面で最終確認できます。
    </p>
    <form method="POST" action="{{ route('admin.bimoni-sharepoy-links.bulk-confirm') }}" class="flex gap-3">
        @csrf
        <textarea name="pasted_text" hidden>{{ $pastedText }}</textarea>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2" @disabled(count($groups) === 0 && count($amountGroups) === 0)>
            この内容で確認する
        </button>
        <a href="{{ route('admin.bimoni-sharepoy-links.index') }}" class="text-sm text-gray-500 px-4 py-2">戻る</a>
    </form>
</div>
@endsection
