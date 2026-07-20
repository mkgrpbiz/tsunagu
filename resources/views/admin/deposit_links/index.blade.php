@extends('layouts.admin')

@section('title', '着金紐付け')

@section('content')
<h1 class="text-xl font-semibold mb-6">着金紐付け</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <form method="GET" action="{{ route('admin.deposit-links.index') }}" class="grid grid-cols-3 gap-4 items-end">
        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">カテゴリー（絞り込み）</label>
            <select name="category_id" id="category_id" onchange="this.form.submit()"
                    class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">指定なし</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">案件名（絞り込み）</label>
            <select name="project_id" id="project_id" onchange="this.form.submit()"
                    class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">指定なし</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected($projectId == $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="q" class="block text-sm font-medium text-gray-700 mb-1">名前・フリガナ・LINE名で検索</label>
            <div class="flex gap-2">
                <input type="text" name="q" id="q" value="{{ $q }}"
                       class="flex-1 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">検索</button>
            </div>
        </div>
    </form>
</div>

@if ($q !== '')
    <h2 class="text-sm font-medium text-gray-700 mb-3">該当する問い合わせ候補</h2>

    @foreach ($candidates as $candidate)
        <form id="deposit-form-{{ $candidate->id }}" method="POST" action="{{ route('admin.deposit-links.store', $candidate) }}">
            @csrf
            <input type="hidden" name="category_id" value="{{ $categoryId }}">
            <input type="hidden" name="project_id" value="{{ $projectId }}">
            <input type="hidden" name="q" value="{{ $q }}">
        </form>
    @endforeach

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">パートナー</th>
                    <th class="px-4 py-3 font-medium">案件名</th>
                    <th class="px-4 py-3 font-medium">LINE名</th>
                    <th class="px-4 py-3 font-medium">名前</th>
                    <th class="px-4 py-3 font-medium">フリガナ</th>
                    <th class="px-4 py-3 font-medium">メールアドレス</th>
                    <th class="px-4 py-3 font-medium">着金日</th>
                    <th class="px-4 py-3 font-medium">着金金額</th>
                    <th class="px-4 py-3 font-medium">パートナー報酬</th>
                    <th class="px-4 py-3 font-medium w-24"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($candidates as $candidate)
                    <tr>
                        <td class="px-4 py-3">{{ $candidate->agency->name }}</td>
                        <td class="px-4 py-3">{{ $candidate->project->name }}</td>
                        <td class="px-4 py-3">{{ $candidate->lineUser->display_name ?? $candidate->legacy_line_display_name }}</td>
                        <td class="px-4 py-3">{{ $candidate->name }}</td>
                        <td class="px-4 py-3">{{ $candidate->name_kana }}</td>
                        <td class="px-4 py-3">{{ $candidate->email }}</td>
                        <td class="px-4 py-3">
                            <input type="date" name="deposit_date" required form="deposit-form-{{ $candidate->id }}" class="rounded-md border border-gray-300 text-sm">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" name="deposit_amount" required min="0" form="deposit-form-{{ $candidate->id }}" class="w-28 rounded-md border border-gray-300 text-sm">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" name="agency_reward_amount" min="0" form="deposit-form-{{ $candidate->id }}" class="w-28 rounded-md border border-gray-300 text-sm" placeholder="{{ $candidate->project->agency_unit_price }}">
                        </td>
                        <td class="px-4 py-3">
                            <button type="submit" form="deposit-form-{{ $candidate->id }}" class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md px-3 py-1.5">紐付け</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-6 text-center text-gray-400">該当する問い合わせがありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
