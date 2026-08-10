@extends('layouts.admin')

@section('title', 'BIMONI(SharePoy) - ユーザー着金履歴反映')

@section('content')
<h1 class="text-xl font-semibold mb-6">ユーザー着金履歴反映の確認</h1>

<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-gray-700">
    <p>月末締め翌月10日サイクルの着金報告用です。紹介ポイント・A01一括紐付けとは別に実行されます。</p>
</div>

<form method="POST" action="{{ route('admin.bimoni-sharepoy-links.history-execute') }}">
    @csrf
    <textarea name="pasted_text" hidden>{{ $pastedText }}</textarea>

    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">SharePoy+ユーザーの着金履歴（{{ $recordCount }}件）</h2>
        @if (count($recordAmountGroups) > 0)
            <table class="w-full text-sm mb-2">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recordAmountGroups as $group)
                        <tr>
                            <td class="py-1 text-gray-700">{{ number_format($group['amount']) }}円</td>
                            <td class="py-1 text-right text-gray-700">{{ $group['count'] }}件</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-400">対象がありません。</p>
        @endif
        @if ($noMatchCount > 0)
            <p class="text-sm text-red-600 mt-1">{{ $noMatchCount }}件は名前・フリガナが一致しないため記録されません。</p>
        @endif
    </div>

    @if (count($candidates) > 0)
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-1">名前かフリガナの片方だけ一致する候補</h2>
            <p class="text-xs text-gray-500 mb-3">チェックを入れて実行すると、その候補のSharePoy+ユーザーとして着金履歴に記録します。チェックしなければ「一致しない」扱いのままです。</p>
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

    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex gap-3">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">
            履歴確定
        </button>
        <a href="{{ route('admin.bimoni-sharepoy-links.index') }}" class="text-sm text-gray-500 px-4 py-2">戻る</a>
    </div>
</form>
@endsection
