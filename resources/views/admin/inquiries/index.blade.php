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
                    <td class="px-4 py-3">{{ $inquiry->lineUser->display_name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->name_kana }}</td>
                    <td class="px-4 py-3">{{ $inquiry->email }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statusColor = match ($inquiry->status) {
                                \App\Enums\InquiryStatus::New => 'bg-blue-50 text-blue-700 border-blue-200',
                                \App\Enums\InquiryStatus::Guided => 'bg-purple-50 text-purple-700 border-purple-200',
                                \App\Enums\InquiryStatus::Contracted => 'bg-green-50 text-green-700 border-green-200',
                                \App\Enums\InquiryStatus::Lost => 'bg-gray-100 text-gray-500 border-gray-200',
                            };
                        @endphp
                        <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $statusColor }}">{{ $inquiry->status->label() }}</span>

                        @if ($inquiry->status !== \App\Enums\InquiryStatus::Contracted)
                            <form method="POST" action="{{ route('admin.inquiries.toggle-lost', $inquiry) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="ml-1 text-xs text-gray-400 hover:text-gray-600 hover:underline">
                                    {{ $inquiry->status === \App\Enums\InquiryStatus::Lost ? '取り消す' : '失注にする' }}
                                </button>
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
@endsection
