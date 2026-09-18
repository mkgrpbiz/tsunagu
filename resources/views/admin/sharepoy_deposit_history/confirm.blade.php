@extends('layouts.admin')

@section('title', 'SharePoy着金履歴管理 - 確認')

@section('content')
<h1 class="text-xl font-semibold mb-6">SharePoy着金履歴管理 - 確認</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-4">
    <p class="text-sm text-gray-700 mb-1">ラベル: {{ $label }}</p>
    <p class="text-sm text-gray-700 mb-1">対象: {{ count($result['groups']) + count($result['noReferrerCode']) }}件</p>
    @if (count($result['noReferrerCode']) > 0)
        <p class="text-sm text-amber-600">紹介コード未登録(ポイント付与対象外): {{ count($result['noReferrerCode']) }}件</p>
    @endif
    @if (count($result['unmatched']) > 0)
        <p class="text-sm text-red-600">非マッチ: {{ count($result['unmatched']) }}件</p>
    @endif
</div>

@if (count($result['groups']) > 0)
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-700">コピー用一覧(紹介者の紹介コード・ポイント・ラベル)</h3>
            <button type="button" onclick="copyToClipboard({{ Illuminate\Support\Js::from($result['copyText']) }})"
                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md px-3 py-1.5">コピー</button>
        </div>
        <textarea readonly rows="{{ count($result['groups']) }}" class="w-full rounded-md border border-gray-300 font-mono text-xs bg-gray-50">{{ $result['copyText'] }}</textarea>
    </div>
@endif

@if (count($result['groups']) + count($result['noReferrerCode']) > 0)
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-4">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-2 font-medium">ユーザーID</th>
                    <th class="text-left px-4 py-2 font-medium">紹介コード</th>
                    <th class="text-left px-4 py-2 font-medium">名前</th>
                    <th class="text-right px-4 py-2 font-medium">件数</th>
                    <th class="text-right px-4 py-2 font-medium">ポイント</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($result['groups'] as $group)
                    <tr class="even:bg-gray-50 hover:bg-gray-100">
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.sharepoy-users.show', $group['sharePoyUser']) }}" class="text-blue-600 hover:underline">{{ $group['sharePoyUser']->sharepoy_user_id }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $group['sharePoyUser']->referrer_sharepoy_user_id }}</td>
                        <td class="px-4 py-2">{{ $group['name'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $group['count'] }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($group['points']) }}pt</td>
                    </tr>
                @endforeach
                @foreach ($result['noReferrerCode'] as $group)
                    <tr class="even:bg-gray-50 hover:bg-gray-100">
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.sharepoy-users.show', $group['sharePoyUser']) }}" class="text-blue-600 hover:underline">{{ $group['sharePoyUser']->sharepoy_user_id }}</a>
                        </td>
                        <td class="px-4 py-2 text-amber-600">未登録</td>
                        <td class="px-4 py-2">{{ $group['name'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $group['count'] }}</td>
                        <td class="px-4 py-2 text-right text-gray-400">{{ number_format($group['points']) }}pt(付与対象外)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($result['unmatched']) > 0)
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
        <p class="text-sm font-medium text-red-700 mb-2">非マッチ(SharePoy+管理に該当ユーザーがいません)</p>
        <ul class="text-xs text-red-600 space-y-1">
            @foreach ($result['unmatched'] as $u)
                <li>{{ $u['name'] }}(件数: {{ $u['count'] }})</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.sharepoy-deposit-history.store') }}" class="flex gap-3">
    @csrf
    <input type="hidden" name="label" value="{{ $label }}">
    @foreach ($contractIds as $id)
        <input type="hidden" name="contract_ids[]" value="{{ $id }}">
    @endforeach
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">確定して履歴に記録</button>
    <a href="{{ route('admin.sharepoy-deposit-history.index') }}" class="text-sm text-gray-500 px-4 py-2">戻る</a>
</form>
@endsection
