@extends('layouts.admin')

@section('title', 'BIMONI(SharePoy) - 実行履歴')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">BIMONI(SharePoy) 実行履歴</h1>
    <a href="{{ route('admin.bimoni-sharepoy-links.index') }}" class="text-sm text-gray-500">一括紐付け画面へ戻る</a>
</div>

<p class="text-sm text-gray-500 mb-3">「履歴確定」「一括紐付け」を実行した時点でのDB状態に基づく一致・不一致の内訳です。後からSharePoy+ユーザーが追加されても、ここの内容は実行当時のまま変わりません。</p>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">実行日時</th>
                <th class="px-4 py-3 font-medium">種別</th>
                <th class="px-4 py-3 font-medium text-right">記録件数</th>
                <th class="px-4 py-3 font-medium text-right">未一致件数</th>
                <th class="px-4 py-3 font-medium">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($logs as $log)
                <tr class="even:bg-gray-50 hover:bg-gray-100 align-top">
                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">
                        @if ($log->kind === 'history')
                            <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">着金履歴反映</span>
                        @else
                            <span class="text-xs bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded">紹介ポイント・一括紐付け</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">{{ $log->saved_count }}</td>
                    <td class="px-4 py-3 text-right {{ $log->unmatched_count > 0 ? 'text-red-600' : '' }}">{{ $log->unmatched_count }}</td>
                    <td class="px-4 py-3">
                        <details>
                            <summary class="cursor-pointer text-blue-600 text-xs select-none">内訳を見る</summary>
                            <div class="mt-2 space-y-2">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-1">処理結果</p>
                                    <pre class="text-xs bg-gray-50 border border-gray-200 rounded p-2 overflow-x-auto">{{ json_encode($log->summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                </div>
                                <details>
                                    <summary class="cursor-pointer text-gray-500 text-xs select-none">貼り付け元テキスト</summary>
                                    <pre class="mt-1 text-xs bg-gray-50 border border-gray-200 rounded p-2 overflow-x-auto whitespace-pre-wrap">{{ $log->pasted_text }}</pre>
                                </details>
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">実行履歴がありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $logs->links() }}
</div>
@endsection
