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

    <div class="space-y-3">
        @forelse ($candidates as $candidate)
            @php
                $project = $candidate->project;
                $tsunaguPrice = $project->singleTsunaguUnitPrice();
                $agencyPrice = $project->singleAgencyUnitPrice();
                $existingCount = $candidate->contracts->count();
            @endphp
            <div class="bg-white border border-gray-300 shadow-sm rounded-lg overflow-hidden tsn-deposit-row">
                <div class="grid grid-cols-6 gap-3 text-sm p-4 bg-blue-50">
                    <div><span class="text-gray-400 text-xs block">問い合わせ日時</span>{{ $candidate->inquired_at?->format('Y-m-d H:i') }}</div>
                    <div><span class="text-gray-400 text-xs block">パートナー</span>{{ $candidate->agency->name }}</div>
                    <div>
                        <span class="text-gray-400 text-xs block">案件名</span>
                        {{ $project->name }}
                        @if ($existingCount > 0)
                            <span class="ml-1 text-xs font-medium border rounded-full px-1.5 py-0.5 bg-amber-50 text-amber-700 border-amber-200">紐付け済み{{ $existingCount }}件</span>
                        @endif
                    </div>
                    <div><span class="text-gray-400 text-xs block">LINE名</span>{{ $candidate->lineUser->display_name ?? $candidate->legacy_line_display_name }}</div>
                    <div><span class="text-gray-400 text-xs block">名前</span>{{ $candidate->name }}</div>
                    <div><span class="text-gray-400 text-xs block">フリガナ</span>{{ $candidate->name_kana }}</div>
                </div>
                <div class="p-4">
                    <div class="tsn-lines space-y-2 mb-2">
                        <div class="grid grid-cols-7 gap-3 items-end text-sm tsn-line">
                            <div>
                                <span class="text-gray-400 text-xs block">TSUNAGU単価</span>
                                <input type="number" name="lines[0][tsunagu_unit_price]" min="0" required
                                       form="deposit-form-{{ $candidate->id }}"
                                       class="tsn-tsunagu-price w-24 rounded-md border border-gray-300 text-sm"
                                       value="{{ $tsunaguPrice }}" placeholder="{{ $tsunaguPrice === null ? '金額' : '' }}">
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs block">パートナー単価</span>
                                <input type="number" name="lines[0][agency_unit_price]" min="0" required
                                       form="deposit-form-{{ $candidate->id }}"
                                       class="tsn-agency-price w-24 rounded-md border border-gray-300 text-sm"
                                       value="{{ $agencyPrice }}" placeholder="{{ $agencyPrice === null ? '金額' : '' }}">
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs block">件数</span>
                                <input type="number" name="lines[0][count]" min="1" step="1" value="1" required
                                       form="deposit-form-{{ $candidate->id }}"
                                       class="tsn-count-input w-20 rounded-md border border-gray-300 text-sm">
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs block">TSUNAGU合計</span>
                                <input type="number" readonly tabindex="-1"
                                       class="tsn-tsunagu-total w-28 rounded-md border border-gray-300 text-sm bg-gray-100">
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs block">パートナー合計</span>
                                <input type="number" readonly tabindex="-1"
                                       class="tsn-agency-total w-28 rounded-md border border-gray-300 text-sm bg-gray-100">
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs block">TSUNAGU利益</span>
                                <span class="tsn-profit-display font-medium">—</span>
                            </div>
                            <div>
                                <button type="button" class="tsn-remove-line text-gray-400 hover:text-red-600 text-sm px-1" title="削除">×</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" class="tsn-add-line text-xs text-blue-600 hover:underline">+ もう1パターン追加</button>
                        <button type="submit" form="deposit-form-{{ $candidate->id }}" class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md px-3 py-1.5">紐付け</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white border border-gray-200 rounded-lg px-4 py-6 text-center text-gray-400">該当する問い合わせがありません。</div>
        @endforelse
    </div>
@endif

<script>
function tsnBindLine(line) {
    var tsunaguPriceInput = line.querySelector('.tsn-tsunagu-price');
    var agencyPriceInput = line.querySelector('.tsn-agency-price');
    var countInput = line.querySelector('.tsn-count-input');
    var tsunaguTotalInput = line.querySelector('.tsn-tsunagu-total');
    var agencyTotalInput = line.querySelector('.tsn-agency-total');
    var profitDisplay = line.querySelector('.tsn-profit-display');

    function recalculate() {
        var tsunaguPrice = parseInt(tsunaguPriceInput.value, 10);
        var agencyPrice = parseInt(agencyPriceInput.value, 10);
        var count = parseInt(countInput.value, 10);

        if (isNaN(tsunaguPrice) || isNaN(agencyPrice) || isNaN(count)) {
            tsunaguTotalInput.value = '';
            agencyTotalInput.value = '';
            profitDisplay.textContent = '—';
            return;
        }

        var tsunaguTotal = tsunaguPrice * count;
        var agencyTotal = agencyPrice * count;
        tsunaguTotalInput.value = tsunaguTotal;
        agencyTotalInput.value = agencyTotal;
        profitDisplay.textContent = '¥' + (tsunaguTotal - agencyTotal).toLocaleString();
    }

    [tsunaguPriceInput, agencyPriceInput, countInput].forEach(function (input) {
        input.addEventListener('input', recalculate);
    });

    recalculate();
}

document.querySelectorAll('.tsn-line').forEach(tsnBindLine);

document.querySelectorAll('.tsn-deposit-row').forEach(function (row) {
    var linesContainer = row.querySelector('.tsn-lines');
    var addButton = row.querySelector('.tsn-add-line');

    addButton.addEventListener('click', function () {
        var lines = linesContainer.querySelectorAll('.tsn-line');
        var newIndex = lines.length;
        var template = lines[0].cloneNode(true);

        template.querySelectorAll('input').forEach(function (input) {
            input.name = input.name.replace(/lines\[\d+\]/, 'lines[' + newIndex + ']');
            if (!input.readOnly) {
                input.value = input.classList.contains('tsn-count-input') ? '1' : '';
            }
        });
        template.querySelector('.tsn-profit-display').textContent = '—';

        linesContainer.appendChild(template);
        tsnBindLine(template);
    });
});

document.addEventListener('click', function (e) {
    if (!e.target.classList.contains('tsn-remove-line')) {
        return;
    }
    var line = e.target.closest('.tsn-line');
    var linesContainer = line.parentElement;
    if (linesContainer.querySelectorAll('.tsn-line').length > 1) {
        line.remove();
        linesContainer.querySelectorAll('.tsn-line').forEach(function (l, i) {
            l.querySelectorAll('input').forEach(function (input) {
                input.name = input.name.replace(/lines\[\d+\]/, 'lines[' + i + ']');
            });
        });
    }
});
</script>
@endsection
