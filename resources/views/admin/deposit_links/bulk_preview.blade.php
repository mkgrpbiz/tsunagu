@extends('layouts.admin')

@section('title', '一括紐付けプレビュー')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">一括紐付けプレビュー</h1>
    <a href="{{ route('admin.deposit-links.index') }}" class="text-sm text-blue-600 hover:underline">戻る</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <p class="text-sm text-gray-700">
        案件: <span class="font-semibold">{{ $project->name }}</span><br>
        一致: <span class="font-semibold">{{ count($matched) }}件</span>
        不一致: <span class="font-semibold text-red-600">{{ count($unmatched) }}件</span>
    </p>
    <p class="text-xs text-gray-500 mt-1">内容を確認のうえ、問題なければ下の「この内容で紐付けを確定する」を押してください。不一致の行は紐付けされません。</p>
</div>

@if (count($matched) > 0)
    <h2 class="text-sm font-medium text-gray-700 mb-3">一致した行（紐付け対象）</h2>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">問い合わせ日時</th>
                    <th class="px-4 py-3 font-medium">パートナー</th>
                    <th class="px-4 py-3 font-medium">案件名</th>
                    <th class="px-4 py-3 font-medium">名前</th>
                    <th class="px-4 py-3 font-medium">フリガナ</th>
                    <th class="px-4 py-3 font-medium text-right">TSUNAGU単価</th>
                    <th class="px-4 py-3 font-medium text-right">パートナー単価</th>
                    <th class="px-4 py-3 font-medium text-right">件数</th>
                    <th class="px-4 py-3 font-medium text-right">TSUNAGU合計</th>
                    <th class="px-4 py-3 font-medium text-right">パートナー合計</th>
                    <th class="px-4 py-3 font-medium">備考</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($matched as $row)
                    @php $inquiry = $row['inquiry']; @endphp
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $inquiry->inquired_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $inquiry->agency->name }}</td>
                        <td class="px-4 py-3">{{ $inquiry->project->name }}</td>
                        <td class="px-4 py-3">{{ $inquiry->name }}</td>
                        <td class="px-4 py-3">{{ $inquiry->name_kana }}</td>
                        <td class="px-4 py-3 text-right">¥{{ number_format($row['tsunagu_price']) }}</td>
                        <td class="px-4 py-3 text-right">¥{{ number_format($row['agency_price']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['count'] }}</td>
                        <td class="px-4 py-3 text-right">¥{{ number_format($row['tsunagu_price'] * $row['count']) }}</td>
                        <td class="px-4 py-3 text-right">¥{{ number_format($row['agency_price'] * $row['count']) }}</td>
                        <td class="px-4 py-3">
                            @if ($inquiry->contracts->count() > 0)
                                <span class="text-xs font-medium border rounded-full px-1.5 py-0.5 bg-amber-50 text-amber-700 border-amber-200">既存{{ $inquiry->contracts->count() }}件あり</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($unmatched) > 0)
    <h2 class="text-sm font-medium text-gray-700 mb-3">不一致だった行（紐付けされません）</h2>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">貼り付けた行</th>
                    <th class="px-4 py-3 font-medium">理由</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($unmatched as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $row['raw'] }}</td>
                        <td class="px-4 py-3 text-red-600">{{ $row['reason'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($matched) > 0)
    <form method="POST" action="{{ route('admin.deposit-links.bulk-store') }}" onsubmit="return confirm('{{ count($matched) }}件を紐付けます。よろしいですか？');">
        @csrf
        <input type="hidden" name="project_id" value="{{ $project->id }}">
        <input type="hidden" name="pasted_text" value="{{ $pastedText }}">
        <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">この内容で紐付けを確定する</button>
    </form>
@endif
@endsection
