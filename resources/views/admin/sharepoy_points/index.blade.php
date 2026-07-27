@extends('layouts.admin')

@section('title', 'SharePoyポイント用')

@section('content')
<h1 class="text-xl font-semibold mb-6">SharePoyポイント用</h1>

<p class="text-xs text-gray-500 mb-6">商品受け取りモニター・覆面調査モニターでA01(シェアポイ)処理された着金のうち、まだSharePoy+ユーザーの履歴に記録していない分を名前で紐付け、件数×300ポイントで集計します。確定すると記録済みになり、次回以降は対象外になります。</p>

@foreach ([['data' => $productMonitor, 'title' => '商品受け取りモニター', 'route' => 'admin.sharepoy-points.store-product-monitor'], ['data' => $mysteryShopper, 'title' => '覆面調査モニター', 'route' => 'admin.sharepoy-points.store-mystery-shopper']] as $section)
    <h2 class="text-lg font-semibold mb-3">{{ $section['title'] }}</h2>

    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-4">
        <p class="text-sm text-gray-700 mb-1">紹介コード: {{ count($section['data']['groups']) }}件</p>
        @if (count($section['data']['unmatched']) > 0)
            <p class="text-sm text-red-600">非マッチ: {{ count($section['data']['unmatched']) }}件</p>
        @endif
    </div>

    @if (count($section['data']['groups']) > 0)
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-700">コピー用一覧(紹介コード・名前・ポイント・ラベル)</h3>
                <button type="button" onclick="copyToClipboard({{ Illuminate\Support\Js::from($section['data']['copyText']) }})"
                        class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md px-3 py-1.5">コピー</button>
            </div>
            <textarea readonly rows="{{ count($section['data']['groups']) }}" class="w-full rounded-md border border-gray-300 font-mono text-xs bg-gray-50">{{ $section['data']['copyText'] }}</textarea>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium">紹介コード</th>
                        <th class="text-left px-4 py-2 font-medium">名前</th>
                        <th class="text-right px-4 py-2 font-medium">件数</th>
                        <th class="text-right px-4 py-2 font-medium">ポイント</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($section['data']['groups'] as $group)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('admin.sharepoy-users.show', $group['sharePoyUser']) }}" class="text-blue-600 hover:underline">{{ $group['sharePoyUser']->sharepoy_user_id }}</a>
                            </td>
                            <td class="px-4 py-2">{{ $group['name'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $group['count'] }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($group['points']) }}pt</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (count($section['data']['unmatched']) > 0)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-red-700 mb-2">非マッチ(SharePoy+管理に該当ユーザーがいません)</p>
            <ul class="text-xs text-red-600 space-y-1">
                @foreach ($section['data']['unmatched'] as $u)
                    <li>{{ $u['name'] }}(件数: {{ $u['count'] }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route($section['route']) }}" class="mb-10">
        @csrf
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2" @disabled(count($section['data']['groups']) === 0 && count($section['data']['unmatched']) === 0)>
            確定して履歴に記録
        </button>
    </form>
@endforeach
@endsection
