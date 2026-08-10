@extends('layouts.admin')

@section('title', 'BIMONI(SharePoy) - 最終確認')

@section('content')
<h1 class="text-xl font-semibold mb-6">最終確認</h1>

<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-gray-700">
    <p>この内容で実行すると、以下を1つの操作としてまとめて登録します。実行後の取り消しはできません。</p>
</div>

<form method="POST" action="{{ route('admin.bimoni-sharepoy-links.bulk-execute') }}">
    @csrf
    <textarea name="pasted_text" hidden>{{ $pastedText }}</textarea>

    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">① SharePoy+ユーザーの着金履歴に記録</h2>
        <p class="text-sm text-gray-700">{{ $recordCount }}件を記録します。</p>
        @if ($noMatchCount > 0)
            <p class="text-sm text-red-600 mt-1">{{ $noMatchCount }}件は名前・フリガナが一致しないため記録されません。</p>
        @endif
    </div>

    @if (count($candidates) > 0)
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-1">名前かフリガナの片方だけ一致する候補</h2>
            <p class="text-xs text-gray-500 mb-3">チェックを入れて実行すると、その候補のSharePoy+ユーザーとして着金履歴に記録します。チェックしなければ①の「一致しない」扱いのままです。</p>
            <div class="divide-y divide-gray-100">
                @foreach ($candidates as $c)
                    <label class="flex items-center gap-3 py-2 text-sm cursor-pointer">
                        <input type="checkbox" name="accept_candidates[]" value="{{ $c['key'] }}" class="rounded border-gray-300">
                        <span class="text-gray-700">貼付: {{ $c['name'] }}（{{ $c['nameKana'] }}）</span>
                        <span class="text-gray-400">→</span>
                        <span class="text-gray-700">候補: {{ $c['candidate']->name }}（{{ $c['candidate']->name_kana }}）</span>
                        <span class="text-gray-400 text-xs">{{ count($c['rows']) }}件</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">② A01（シェアポイ）への一括着金紐付け</h2>
        @if (count($amountGroups) > 0)
            <table class="w-full text-sm mb-2">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($amountGroups as $group)
                        <tr>
                            <td class="py-1 text-gray-700">BIMONI　{{ number_format($group['amount']) }}円</td>
                            <td class="py-1 text-right text-gray-700">{{ $group['count'] }}件</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-sm font-medium text-gray-700">合計 {{ $depositLinkCount }}件をA01（シェアポイ）名義でBIMONI【募集モニター30件以上】に紐付けます。</p>
        @else
            <p class="text-sm text-gray-400">対象がありません。</p>
        @endif
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex gap-3">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">
            実行する
        </button>
        <a href="{{ route('admin.bimoni-sharepoy-links.index') }}" class="text-sm text-gray-500 px-4 py-2">戻る</a>
    </div>
</form>
@endsection
