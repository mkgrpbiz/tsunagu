@extends('layouts.admin')

@section('title', 'BIMONI(SharePoy)')

@section('content')
<h1 class="text-xl font-semibold mb-6">BIMONI(SharePoy) 一括紐付け</h1>

<div class="bg-amber-50 border border-amber-100 rounded-lg p-4 mb-6 text-sm text-gray-700 space-y-1">
    <p class="font-bold text-amber-600">サイクルが異なる2つの処理があるので、別々に実行します。</p>
    <p>・ユーザー着金履歴反映 → 名前・フリガナでSharePoy+管理のユーザーと照合し、着金履歴に記録。ディスコードでの着金報告に使う分で、月末締め翌月10日サイクル。</p>
    <p>・紹介ポイント・一括紐付け → 紹介コード列を使ったSharePoy+のポイント付与用コピーテキストと、金額列を使ったA01（シェアポイ）へのTSUNAGU側一括着金紐付け。こちらは月末締め翌月末サイクル。</p>
    <p>同じ貼り付け内容から、下のどちらか一方のボタン、または両方を実行できます。</p>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-xs text-gray-500 mb-3">
        「日付 - 紹介コード - 名前 - フリガナ - 商品名 - 金額」の順にタブ区切りで貼り付けてください。1行1件です。日付は参考表示のみです。
    </p>
    <form method="POST">
        @csrf
        <textarea name="pasted_text" rows="10" required placeholder="2026/07/25&#9;SHAREPOY&#9;宮田麻美&#9;ミヤタマミ&#9;鮫珠&#9;¥1,000"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <div class="mt-2 flex gap-3">
            <button type="submit" formaction="{{ route('admin.bimoni-sharepoy-links.history-confirm') }}"
                    class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">
                ユーザー着金履歴反映
            </button>
            <button type="submit" formaction="{{ route('admin.bimoni-sharepoy-links.link-confirm') }}"
                    class="text-sm bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-md px-4 py-2">
                紹介ポイント・一括紐付け
            </button>
        </div>
    </form>
</div>
@endsection
