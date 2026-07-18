@extends('layouts.agency')

@section('title', '問い合わせ')

@section('content')
<h1 class="text-xl font-semibold mb-6">問い合わせ</h1>

<form method="GET" action="{{ route('agency.inquiries.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label for="project_id" class="block text-xs font-medium text-gray-700 mb-1">案件で絞り込み</label>
        <select name="project_id" id="project_id" onchange="this.form.submit()" class="rounded-md border border-gray-300 text-sm">
            <option value="">すべての案件</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected($projectId == $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="month" class="block text-xs font-medium text-gray-700 mb-1">月で絞り込み</label>
        <div class="flex gap-2">
            <select name="month" id="month" onchange="this.form.submit()" class="rounded-md border border-gray-300 text-sm">
                @foreach ($months as $ym)
                    <option value="{{ $ym }}" @selected($month === $ym)>{{ $ym }}</option>
                @endforeach
            </select>
            <button type="submit" name="month" value="all" class="text-sm font-medium rounded-md px-3 {{ ! $month ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">累計</button>
        </div>
    </div>
    @if ($projectId || $month)
        <a href="{{ route('agency.inquiries.index') }}" class="text-sm text-gray-500">絞り込み解除</a>
    @endif
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

<h2 class="text-sm font-semibold text-gray-700 mb-3">案件別実績{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-2 font-medium">案件名</th>
                <th class="px-4 py-2 font-medium">問い合わせ数</th>
                <th class="px-4 py-2 font-medium">着金数</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($projectSummary as $projectName => $stats)
                <tr>
                    <td class="px-4 py-2">{{ $projectName }}</td>
                    <td class="px-4 py-2">{{ $stats['count'] }}件</td>
                    <td class="px-4 py-2">{{ $stats['contracted'] }}件</td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-4 text-center text-gray-400">データがありません。</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">問い合わせ一覧{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">問い合わせ日時</th>
                <th class="px-4 py-3 font-medium">案件名</th>
                <th class="px-4 py-3 font-medium">LINE名</th>
                <th class="px-4 py-3 font-medium">名前</th>
                <th class="px-4 py-3 font-medium">フリガナ</th>
                <th class="px-4 py-3 font-medium">ステータス</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($inquiries as $inquiry)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $inquiry->inquired_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">{{ $inquiry->project->name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->lineUser->display_name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->name }}</td>
                    <td class="px-4 py-3">{{ $inquiry->name_kana }}</td>
                    <td class="px-4 py-3">{{ $inquiry->status->label() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">該当する問い合わせがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
