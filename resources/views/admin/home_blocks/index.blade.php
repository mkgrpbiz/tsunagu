@extends('layouts.admin')

@section('title', 'ホーム管理')

@push('styles')
@include('partials.home_block_styles')
<style>
.hb-list{list-style:none;margin:0;padding:0;max-width:520px}
.hb-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;margin-bottom:16px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.03)}
.hb-card.dragging{opacity:.4}
.hb-card.drag-over{border-color:#2563eb;border-style:dashed}
.hb-toolbar{display:flex;align-items:center;gap:10px;padding:8px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb}
.hb-handle{cursor:grab;color:#9ca3af;font-size:16px;line-height:1;user-select:none;flex-shrink:0}
.hb-handle:active{cursor:grabbing}
.hb-badge{flex-shrink:0;font-size:11px;font-weight:700;border-radius:999px;padding:3px 9px;white-space:nowrap;background:#eef2ff;color:#4338ca}
.hb-toolbar .hb-actions{margin-left:auto;display:flex;gap:12px;font-size:12.5px}
.hb-preview{padding:16px}
.hb-preview .tsn-home{max-width:none;margin:0}
</style>
@endpush

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">ホーム管理</h1>
    <a href="{{ route('admin.home-blocks.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">ブロック追加</a>
</div>

<div class="flex gap-4 mb-6 text-sm">
    <a href="{{ route('admin.home-content.edit') }}" class="text-blue-600 hover:underline">ホームテキスト設定（ヘッダー・締めの文言）</a>
    <a href="{{ route('admin.sales-materials.index') }}" class="text-blue-600 hover:underline">営業素材（PDF）を管理</a>
    <a href="{{ route('admin.announcements.index') }}" class="text-blue-600 hover:underline">お知らせを管理</a>
</div>

<p class="text-sm text-gray-500 mb-4">
    実際のホーム画面に表示されるままのプレビューです。<span class="font-semibold">ドラッグ＆ドロップで並び替え</span>できます。「パートナー紹介」「営業素材」「お知らせ」ブロックは表示位置のみで、中身はそれぞれの管理メニューから編集してください。
</p>

<ul class="hb-list" id="hb-list">
    @php
        $typeLabels = [
            'text' => 'テキスト',
            'image' => '画像',
            'benefits' => 'メリット一覧',
            'cta' => 'CTA',
            'referral_cta' => 'パートナー紹介',
            'collaboration_cta' => '共創パートナー紹介',
            'collaboration_partner_application_cta' => '共創パートナー申請',
            'sales_materials' => '営業素材（位置）',
            'announcements' => 'お知らせ（位置）',
        ];
    @endphp
    @forelse ($blocks as $block)
        <li class="hb-card" draggable="true" data-id="{{ $block->id }}">
            <div class="hb-toolbar">
                <span class="hb-handle">⠿</span>
                <span class="hb-badge">{{ $typeLabels[$block->type] ?? $block->type }}</span>
                <div class="hb-actions">
                    <a href="{{ route('admin.home-blocks.edit', $block) }}" class="text-blue-600 hover:underline">編集</a>
                    <form method="POST" action="{{ route('admin.home-blocks.destroy', $block) }}" onsubmit="return confirm('削除しますか？');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">削除</button>
                    </form>
                </div>
            </div>
            <div class="hb-preview">
                <div class="tsn-home">
                    @include('partials.home_block', ['block' => $block, 'salesMaterials' => $salesMaterials, 'announcements' => $announcements])
                </div>
            </div>
        </li>
    @empty
        <li class="text-center text-gray-400 py-10">ブロックがありません。</li>
    @endforelse
</ul>

<script>
(function () {
    const list = document.getElementById('hb-list');
    if (!list) return;

    let dragging = null;

    list.addEventListener('dragstart', (e) => {
        const card = e.target.closest('.hb-card');
        if (!card) return;
        dragging = card;
        card.classList.add('dragging');
    });

    list.addEventListener('dragend', () => {
        if (dragging) dragging.classList.remove('dragging');
        list.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
        dragging = null;
        saveOrder();
    });

    list.addEventListener('dragover', (e) => {
        e.preventDefault();
        const card = e.target.closest('.hb-card');
        if (!card || card === dragging) return;
        list.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
        card.classList.add('drag-over');

        const rect = card.getBoundingClientRect();
        const before = (e.clientY - rect.top) < rect.height / 2;
        list.insertBefore(dragging, before ? card : card.nextSibling);
    });

    function saveOrder() {
        const order = Array.from(list.querySelectorAll('.hb-card')).map((el) => el.dataset.id);
        fetch(@json(route('admin.home-blocks.reorder')), {
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
