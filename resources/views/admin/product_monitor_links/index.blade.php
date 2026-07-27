@extends('layouts.admin')

@section('title', '商品受け取りモニター')

@section('content')
<h1 class="text-xl font-semibold mb-6">商品受け取りモニター 一括紐付け</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-xs text-gray-500 mb-3">
        スプレッドシートからそのまま貼り付けてください（列: 名前 / 別名 / 商品名A / 数量A / 商品名B / 数量B）。名前で「商品受け取りモニター」の問い合わせを検索し、見つからない場合はA01(シェアポイ)として自動登録します。数量A×1000円がTSUNAGU単価・数量A×500円がパートナー単価、数量B×500円がTSUNAGU単価・数量B×0円がパートナー単価（A01の場合はパートナー単価もTSUNAGU単価と同額）。着金日は登録した日になります。
    </p>
    <form method="POST" action="{{ route('admin.product-monitor-links.bulk-preview') }}">
        @csrf
        <textarea name="pasted_text" rows="10" required
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</div>
@endsection
