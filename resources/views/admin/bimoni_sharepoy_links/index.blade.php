@extends('layouts.admin')

@section('title', 'BIMONI(SharePoy)')

@section('content')
<h1 class="text-xl font-semibold mb-6">BIMONI(SharePoy) 一括紐付け</h1>

<div class="bg-amber-50 border border-amber-100 rounded-lg p-4 mb-6 text-sm text-gray-700 space-y-1">
    <p class="font-bold text-amber-600">貼り付けた1行から、列ごとに3つの処理を行います。</p>
    <p>・紹介コード列（SHAREPOY/SP...）→ SharePoy+のBIMONI紹介ポイント用（件数×300ポイント）。コピーして手動でSharePoyで付与してください。</p>
    <p>・名前・フリガナ列 → コードに関係なく全行をSharePoy+管理のユーザーと照合し、一致した分だけそのユーザーの着金履歴に記録。</p>
    <p>・金額列 → コード・名前の一致有無に関係なく、金額ごとに件数をまとめてA01（シェアポイ）名義でTSUNAGU側に一括着金紐付け。</p>
    <p>着金履歴への記録とA01への一括着金紐付けは1つの操作としてまとめて実行します（プレビュー→最終確認→実行の順）。</p>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-xs text-gray-500 mb-3">
        「日付 - 紹介コード - 名前 - フリガナ - 商品名 - 金額」の順にタブ区切りで貼り付けてください。1行1件です。日付は参考表示のみです。
    </p>
    <form method="POST" action="{{ route('admin.bimoni-sharepoy-links.bulk-preview') }}">
        @csrf
        <textarea name="pasted_text" rows="10" required placeholder="2026/07/25&#9;SHAREPOY&#9;宮田麻美&#9;ミヤタマミ&#9;鮫珠&#9;¥1,000"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</div>
@endsection
