@extends('layouts.admin')

@section('title', 'BIMONI用(TSUNAGU)')

@section('content')
<h1 class="text-xl font-semibold mb-6">BIMONI用(TSUNAGU) 一括紐付け</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-xs text-gray-500 mb-3">
        「日付 - TSUNAGU - 名前 - フリガナ - 商品名 - 金額」の順にタブ区切りで貼り付けてください。1行1件です。日付・商品名は参考表示のみで、着金日は登録した日になります。金額は¥1,000または¥500のみ対応（¥1,000→TSUNAGU単価1,000円/パートナー単価800円、¥500→TSUNAGU単価500円/パートナー単価400円）。名前・フリガナでTSUNAGUの問い合わせを検索し、案件は問いません。
    </p>
    <form method="POST" action="{{ route('admin.bimoni-tsunagu-links.bulk-preview') }}">
        @csrf
        <textarea name="pasted_text" rows="10" required placeholder="2026/07/25&#9;TSUNAGU&#9;宮田麻美&#9;ミヤタマミ&#9;鮫珠&#9;¥1,000"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</div>
@endsection
