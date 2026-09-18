@extends('layouts.admin')

@section('title', 'SharePoy着金履歴管理')

@section('content')
<h1 class="text-xl font-semibold mb-6">SharePoy着金履歴管理</h1>

<p class="text-xs text-gray-500 mb-6">
    BIMONI・商品受け取りモニター・覆面調査モニター以外でA01(シェアポイ)に紐付けられた着金のうち、
    まだSharePoy+ユーザーの着金履歴に記録していない分を一覧表示します。対象を選び、ラベルを入力して「追加」を押すと、
    SharePoyポイント用と同じ形式で紹介コード・ポイントのコピー用一覧を作成できます。
</p>

@if (session('status'))
    <div class="mb-6 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.sharepoy-deposit-history.preview') }}">
    @csrf

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-4">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-2 font-medium">着金日</th>
                    <th class="text-left px-4 py-2 font-medium">案件名</th>
                    <th class="text-left px-4 py-2 font-medium">名前</th>
                    <th class="text-left px-4 py-2 font-medium">フリガナ</th>
                    <th class="text-right px-4 py-2 font-medium">金額</th>
                    <th class="text-right px-4 py-2 font-medium">件数</th>
                    <th class="text-center px-4 py-2 font-medium w-12">選択</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($contracts as $contract)
                    <tr class="even:bg-gray-50 hover:bg-gray-100">
                        <td class="px-4 py-2">{{ $contract->deposit_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-2">{{ $contract->effectiveProject()?->name }}</td>
                        <td class="px-4 py-2">{{ $contract->inquiry->name }}</td>
                        <td class="px-4 py-2">{{ $contract->inquiry->name_kana }}</td>
                        <td class="px-4 py-2 text-right">¥{{ number_format($contract->deposit_amount) }}</td>
                        <td class="px-4 py-2 text-right">{{ $contract->count }}</td>
                        <td class="px-4 py-2 text-center">
                            <input type="checkbox" name="contract_ids[]" value="{{ $contract->id }}">
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">未記録の着金はありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($contracts->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-4 flex items-end gap-3">
            <div class="flex-1">
                <label for="label" class="block text-xs font-medium text-gray-700 mb-1">ラベル(コピー用一覧の3列目に使用)</label>
                <input type="text" name="label" id="label" required placeholder="例: ○○案件紹介"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            <div class="w-32">
                <label for="points_per_line" class="block text-xs font-medium text-gray-700 mb-1">1件あたりポイント</label>
                <input type="number" name="points_per_line" id="points_per_line" required min="1"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">追加</button>
        </div>
    @endif
</form>
@endsection
