@extends('layouts.admin')

@section('title', '案件一覧')

@push('styles')
<style>
.proj-row.dragging{opacity:.4}
.proj-row.drag-over td{border-top:2px dashed #2563eb}
.proj-handle{cursor:grab;color:#9ca3af;user-select:none}
.proj-handle:active{cursor:grabbing}
</style>
@endpush

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">案件一覧</h1>
    <a href="{{ route('admin.projects.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">＋ 新規作成</a>
</div>

@php
    $statusTabs = [
        'all' => ['label' => 'すべて', 'color' => 'bg-gray-500'],
        'published' => ['label' => '公開中', 'color' => 'bg-green-500'],
        'paused' => ['label' => '停止中', 'color' => 'bg-orange-500'],
        'closed' => ['label' => '終了', 'color' => 'bg-gray-500'],
    ];
@endphp
<div class="flex border-b border-gray-200 mb-2">
    @foreach ($statusTabs as $key => $tab)
        @php $count = $key === 'all' ? $totalCount : $statusCounts->get($key, 0); @endphp
        <a href="{{ route('admin.projects.index', array_merge(request()->except('status'), $key === 'all' ? [] : ['status' => $key])) }}"
           class="flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
                  {{ $status === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $tab['label'] }}
            <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white {{ $tab['color'] }}">{{ $count }}</span>
        </a>
    @endforeach
</div>

<div class="flex flex-wrap border-b border-gray-200 mb-4">
    <a href="{{ route('admin.projects.index', request()->except('category')) }}"
       class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium border-b-2 transition-colors
              {{ $categoryId === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        すべてのカテゴリー
        <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white bg-gray-500">{{ $totalCount }}</span>
    </a>
    @foreach ($categories as $category)
        <a href="{{ route('admin.projects.index', array_merge(request()->except('category'), ['category' => $category->id])) }}"
           class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium border-b-2 transition-colors
                  {{ (string) $categoryId === (string) $category->id ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $category->name }}
            <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white bg-gray-500">{{ $categoryCounts->get($category->id, 0) }}</span>
        </a>
    @endforeach
</div>

@if ($canReorder)
    <p class="text-sm text-gray-500 mb-3">ドラッグ＆ドロップで並び替えできます。</p>
@else
    <p class="text-sm text-gray-400 mb-3">並び替えは「すべてのカテゴリー」以外かつステータス「すべて」の表示中のみ可能です。</p>
@endif

<div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                @if ($canReorder)
                    <th class="px-4 py-3 font-medium w-10"></th>
                @endif
                <th class="px-4 py-3 font-medium">案件名</th>
                <th class="px-4 py-3 font-medium">ステータス</th>
                <th class="px-4 py-3 font-medium">カテゴリー</th>
                <th class="px-4 py-3 font-medium">取引先</th>
                <th class="px-4 py-3 font-medium">紹介者</th>
                <th class="px-4 py-3 font-medium text-right">TSUNAGU単価</th>
                <th class="px-4 py-3 font-medium text-right">パートナー単価</th>
                <th class="px-4 py-3 font-medium w-36 text-center">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100" id="proj-list">
            @forelse ($projects as $project)
                <tr class="hover:bg-gray-50 @if ($canReorder) proj-row @endif" @if ($canReorder) draggable="true" data-id="{{ $project->id }}" @endif>
                    @if ($canReorder)
                        <td class="px-4 py-3"><span class="proj-handle">⠿</span></td>
                    @endif
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('admin.projects.edit', $project) }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $project->name }}</a>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $statusColor = match ($project->status) {
                                \App\Enums\ProjectStatus::Published => 'bg-green-50 text-green-700 border-green-200',
                                \App\Enums\ProjectStatus::Paused => 'bg-orange-50 text-orange-700 border-orange-200',
                                \App\Enums\ProjectStatus::Closed => 'bg-gray-50 text-gray-600 border-gray-200',
                            };
                        @endphp
                        <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $statusColor }}">{{ $project->status->label() }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $project->category->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $project->client_name ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $project->referrerAgency->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">{{ $project->tsunagu_unit_price !== null ? '¥'.number_format($project->tsunagu_unit_price) : '変動' }}</td>
                    <td class="px-4 py-3 text-right">{{ $project->agency_unit_price !== null ? '¥'.number_format($project->agency_unit_price) : '変動' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-1 justify-center flex-wrap">
                            <form method="POST" action="{{ route('admin.projects.duplicate', $project) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">複製</button>
                            </form>
                            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="inline" onsubmit="return confirm('削除しますか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">削除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canReorder ? 9 : 8 }}" class="px-4 py-8 text-center text-gray-400">案件がありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($canReorder)
    <script>
    (function () {
        const list = document.getElementById('proj-list');
        if (!list) return;

        let dragging = null;

        list.addEventListener('dragstart', (e) => {
            const row = e.target.closest('.proj-row');
            if (!row) return;
            dragging = row;
            row.classList.add('dragging');
        });

        list.addEventListener('dragend', () => {
            if (dragging) dragging.classList.remove('dragging');
            list.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
            dragging = null;
            saveOrder();
        });

        list.addEventListener('dragover', (e) => {
            e.preventDefault();
            const row = e.target.closest('.proj-row');
            if (!row || row === dragging) return;
            list.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
            row.classList.add('drag-over');

            const rect = row.getBoundingClientRect();
            const before = (e.clientY - rect.top) < rect.height / 2;
            list.insertBefore(dragging, before ? row : row.nextSibling);
        });

        function saveOrder() {
            const order = Array.from(list.querySelectorAll('.proj-row')).map((el) => el.dataset.id);
            fetch(@json(route('admin.projects.reorder')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({ order }),
            });
        }
    })();
    </script>
@endif
@endsection
