@extends('layouts.admin')

@section('title', '問い合わせ一覧')

@section('content')
<h1 class="text-xl font-semibold mb-6">問い合わせ一覧</h1>

<form method="GET" action="{{ route('admin.inquiries.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label for="month" class="block text-xs font-medium text-gray-700 mb-1">月で絞り込み</label>
        <div class="flex gap-2">
            <select name="month" id="month" onchange="this.form.submit()" class="rounded-md border border-gray-300 text-sm">
                <option value="" disabled @selected(! $month)>月を選択</option>
                @foreach ($months as $ym)
                    <option value="{{ $ym }}" @selected($month === $ym)>{{ $ym }}</option>
                @endforeach
            </select>
            <button type="submit" name="month" value="all" class="text-sm font-medium rounded-md px-3 {{ ! $month ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">累計</button>
        </div>
    </div>
</form>

<details class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <summary class="text-sm font-medium text-gray-700 cursor-pointer select-none">一括追加（スプレッドシートから貼り付け）</summary>
    <p class="text-xs text-gray-500 mt-3 mb-3">
        「タイムスタンプ　紹介コード　案件名　LINE名　お名前　フリガナ　メールアドレス」の順にタブ区切りで貼り付けてください（ヘッダー行を含めて貼り付けても自動的に無視されます）。紹介コードはパートナーの本人コード、案件名は登録済み案件の名称（旧表記も含む）と一致させます。追加された問い合わせは案内済みとして登録されます。
    </p>
    <form method="POST" action="{{ route('admin.inquiries.bulk-preview') }}">
        @csrf
        <textarea name="pasted_text" rows="8" required
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</details>

<div class="grid md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">{{ $month ? $month.'の実績' : '全期間実績' }}</p>
        <p class="text-2xl font-semibold mt-1">{{ $monthlyTotal['count'] }}件 <span class="text-sm font-normal text-gray-500">（うち着金 {{ $monthlyTotal['contracted'] }}件）</span></p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">累計実績</p>
        <p class="text-2xl font-semibold mt-1">{{ $cumulativeTotal['count'] }}件 <span class="text-sm font-normal text-gray-500">（うち着金 {{ $cumulativeTotal['contracted'] }}件）</span></p>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">問い合わせ日時</th>
                <th class="px-4 py-3 font-medium">カテゴリー</th>
                <th class="px-4 py-3 font-medium">案件名</th>
                <th class="px-4 py-3 font-medium">パートナー名</th>
                <th class="px-4 py-3 font-medium">LINE名</th>
                <th class="px-4 py-3 font-medium">名前</th>
                <th class="px-4 py-3 font-medium">フリガナ</th>
                <th class="px-4 py-3 font-medium">メールアドレス</th>
                <th class="px-4 py-3 font-medium w-40">ステータス</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($inquiries as $inquiry)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $inquiry->inquired_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">{{ $inquiry->project->category->name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->project->name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->agency->name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->lineUser->display_name ?? $inquiry->legacy_line_display_name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->name_kana }}</td>
                    <td class="px-4 py-3">{{ $inquiry->email }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statusColor = match ($inquiry->status) {
                                \App\Enums\InquiryStatus::New => 'bg-blue-50 text-blue-700 border-blue-200',
                                \App\Enums\InquiryStatus::GuidanceFailed => 'bg-red-50 text-red-700 border-red-200',
                                \App\Enums\InquiryStatus::Guided => 'bg-purple-50 text-purple-700 border-purple-200',
                                \App\Enums\InquiryStatus::Contracted => 'bg-green-50 text-green-700 border-green-200',
                            };
                        @endphp
                        <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $statusColor }}">{{ $inquiry->status->label() }}</span>

                        @if ($inquiry->status === \App\Enums\InquiryStatus::GuidanceFailed)
                            <form method="POST" action="{{ route('admin.inquiries.resend-guidance', $inquiry) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="ml-1 text-xs text-blue-600 hover:underline">再送信</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-6 text-center text-gray-400">問い合わせはまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $inquiries->links() }}
</div>
@endsection
