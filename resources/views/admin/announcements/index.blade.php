@extends('layouts.admin')

@section('title', 'お知らせ一覧')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">お知らせ一覧</h1>
    <a href="{{ route('admin.announcements.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">新規作成</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium w-28">日付</th>
                <th class="px-4 py-3 font-medium w-32">分類</th>
                <th class="px-4 py-3 font-medium">内容</th>
                <th class="px-4 py-3 font-medium w-20">LINE</th>
                <th class="px-4 py-3 font-medium w-32"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($announcements as $announcement)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $announcement->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $announcement->category->color() }}">{{ $announcement->category->label() }}</span>
                    </td>
                    <td class="px-4 py-3">{{ $announcement->body }}</td>
                    <td class="px-4 py-3">
                        @if ($announcement->notify_line)
                            <span class="text-xs font-medium border rounded-full px-2 py-1 bg-green-50 text-green-700 border-green-200">ON</span>
                        @else
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="text-blue-600 hover:underline">編集</a>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="inline" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">削除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">お知らせがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
