@extends('layouts.admin')

@section('title', 'BIMONI(SharePoy)')

@section('content')
<h1 class="text-xl font-semibold mb-6">BIMONI(SharePoy) 一括紐付け</h1>

<div class="bg-amber-50 border border-amber-100 rounded-lg p-4 mb-6 text-sm text-gray-700">
    <p class="font-bold text-amber-600 mb-1">こちらはSharePoy+ポイント付与用です。</p>
    <p>別でTSUNAGUでも一括処理を行なってください。</p>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-xs text-gray-500 mb-3">
        「日付 - 紹介コード - 名前 - フリガナ - 商品名 - 金額」の順にタブ区切りで貼り付けてください。1行1件です。日付・金額は参考表示のみです。紹介コードが「SHAREPOY」の行は名前・フリガナでSharePoy+管理のユーザーを検索して紐付け、「SP」から始まる行はそのままコードとして扱います。同じ紹介コードは合算し、件数×300ポイント（BIMONI紹介）としてコピー用一覧に表示します。
    </p>
    <form method="POST" action="{{ route('admin.bimoni-sharepoy-links.bulk-preview') }}">
        @csrf
        <textarea name="pasted_text" rows="10" required placeholder="2026/07/25&#9;SHAREPOY&#9;宮田麻美&#9;ミヤタマミ&#9;鮫珠&#9;¥1,000"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</div>
@endsection
