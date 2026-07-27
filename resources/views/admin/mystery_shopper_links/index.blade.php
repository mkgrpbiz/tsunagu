@extends('layouts.admin')

@section('title', '覆面調査モニター')

@section('content')
<h1 class="text-xl font-semibold mb-6">覆面調査モニター 一括紐付け</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-xs text-gray-500 mb-3">
        スプレッドシートからそのまま貼り付けてください（列: 名前 / 未使用 / 件数 / 金額）。名前で「覆面調査」の問い合わせを検索し、見つからない場合はA01(シェアポイ)として自動登録します。件数×1000円がTSUNAGU単価・件数×500円がパートナー単価（A01の場合はパートナー単価もTSUNAGU単価と同額）。着金日は登録した日になります。
    </p>
    <form method="POST" action="{{ route('admin.mystery-shopper-links.bulk-preview') }}">
        @csrf
        <textarea name="pasted_text" rows="10" required placeholder="小島伊織&#9;&#9;2&#9;&#9;¥2,000"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</div>
@endsection
