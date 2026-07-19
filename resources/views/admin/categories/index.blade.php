@extends('layouts.admin')

@section('title', 'カテゴリー一覧')

@push('styles')
<style>
.cat-row.dragging{opacity:.4}
.cat-row.drag-over td{border-top:2px dashed #2563eb}
.cat-handle{cursor:grab;color:#9ca3af;user-select:none}
.cat-handle:active{cursor:grabbing}
</style>
@endpush

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">カテゴリー一覧</h1>
    <a href="{{ route('admin.categories.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">新規作成</a>
</div>

<p class="text-sm text-gray-500 mb-3">ドラッグ＆ドロップで並び替えできます。</p>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium w-10"></th>
                <th class="px-4 py-3 font-medium">名称</th>
                <th class="px-4 py-3 font-medium">案件数</th>
                <th class="px-4 py-3 font-medium w-32"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100" id="cat-list">
            @forelse ($categories as $category)
                <tr class="cat-row" draggable="true" data-id="{{ $category->id }}">
                    <td class="px-4 py-3"><span class="cat-handle">⠿</span></td>
                    <td class="px-4 py-3">{{ $category->name }}</td>
                    <td class="px-4 py-3">{{ $category->projects_count }}</td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600 hover:underline">編集</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">削除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">カテゴリーがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
(function () {
    const list = document.getElementById('cat-list');
    if (!list) return;

    let dragging = null;

    list.addEventListener('dragstart', (e) => {
        const row = e.target.closest('.cat-row');
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
        const row = e.target.closest('.cat-row');
        if (!row || row === dragging) return;
        list.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
        row.classList.add('drag-over');

        const rect = row.getBoundingClientRect();
        const before = (e.clientY - rect.top) < rect.height / 2;
        list.insertBefore(dragging, before ? row : row.nextSibling);
    });

    function saveOrder() {
        const order = Array.from(list.querySelectorAll('.cat-row')).map((el) => el.dataset.id);
        fetch(@json(route('admin.categories.reorder')), {
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
@endsection
