@extends('layouts.admin')

@section('title', '合計成果反映')

@section('content')
<h1 class="text-xl font-semibold mb-6">合計成果反映</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <form method="GET" action="{{ route('admin.aggregate-results.index') }}" class="flex gap-2 items-end">
        <div class="flex-1">
            <label for="q" class="block text-sm font-medium text-gray-700 mb-1">会員番号・LINE名・名前・フリガナで検索</label>
            <input type="text" name="q" id="q" value="{{ $q }}"
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">検索</button>
    </form>
</div>

@if ($q !== '')
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
        <h2 class="text-sm font-medium text-gray-700 mb-3">該当するパートナー候補</h2>
        <div class="space-y-2">
            @forelse ($candidates as $candidate)
                <div class="flex items-center justify-between border border-gray-200 rounded-md px-3 py-2 {{ $selectedAgency?->id === $candidate->id ? 'bg-blue-50 border-blue-300' : '' }}">
                    <div class="text-sm grid grid-cols-4 gap-4 flex-1">
                        <div><span class="text-gray-400 text-xs block">会員番号</span>{{ $candidate->referral_code }}</div>
                        <div><span class="text-gray-400 text-xs block">名前</span>{{ $candidate->name }}</div>
                        <div><span class="text-gray-400 text-xs block">フリガナ</span>{{ $candidate->name_kana }}</div>
                        <div><span class="text-gray-400 text-xs block">LINE名</span>{{ $candidate->line_display_name }}</div>
                    </div>
                    <a href="{{ route('admin.aggregate-results.index', ['q' => $q, 'agency_id' => $candidate->id]) }}"
                       class="ml-4 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-3 py-1.5">選択</a>
                </div>
            @empty
                <div class="text-center text-gray-400 py-4">該当するパートナーが見つかりません。</div>
            @endforelse
        </div>
    </div>
@endif

@if ($selectedAgency)
    <div class="bg-white border border-gray-300 shadow-sm rounded-lg overflow-hidden">
        <div class="grid grid-cols-4 gap-4 text-sm p-4 bg-blue-50">
            <div><span class="text-gray-400 text-xs block">会員番号</span>{{ $selectedAgency->referral_code }}</div>
            <div><span class="text-gray-400 text-xs block">名前</span>{{ $selectedAgency->name }}</div>
            <div><span class="text-gray-400 text-xs block">フリガナ</span>{{ $selectedAgency->name_kana }}</div>
            <div><span class="text-gray-400 text-xs block">LINE名</span>{{ $selectedAgency->line_display_name }}</div>
        </div>

        <form method="POST" action="{{ route('admin.aggregate-results.store', $selectedAgency) }}" class="p-4">
            @csrf
            <div class="tsn-lines space-y-2 mb-3">
                <div class="grid grid-cols-7 gap-3 items-end text-sm tsn-line">
                    <div>
                        <span class="text-gray-400 text-xs block">案件名</span>
                        <select name="lines[0][project_id]" required class="tsn-project-select w-full rounded-md border border-gray-300 text-sm">
                            <option value="">選択してください</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs block">TSUNAGU単価</span>
                        <input type="number" name="lines[0][tsunagu_unit_price]" min="0" required
                               class="tsn-tsunagu-price w-full rounded-md border border-gray-300 text-sm">
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs block">パートナー単価</span>
                        <input type="number" name="lines[0][agency_unit_price]" min="0" required
                               class="tsn-agency-price w-full rounded-md border border-gray-300 text-sm">
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs block">件数</span>
                        <input type="number" name="lines[0][count]" min="1" step="1" value="1" required
                               class="tsn-count-input w-full rounded-md border border-gray-300 text-sm">
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs block">合計</span>
                        <span class="tsn-line-total font-medium">—</span>
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs block">パートナー10%</span>
                        @if ($selectedAgency->referred_by_agency_id)
                            <label class="inline-flex items-center gap-1">
                                <input type="hidden" name="lines[0][apply_referral_commission]" value="0" class="tsn-commission-hidden">
                                <input type="checkbox" name="lines[0][apply_referral_commission]" value="1" checked
                                       class="tsn-commission-checkbox rounded border-gray-300">
                                <span class="text-xs text-gray-600">対象</span>
                            </label>
                        @else
                            <span class="text-xs text-gray-400">対象外（紹介元なし）</span>
                        @endif
                    </div>
                    <div>
                        <button type="button" class="tsn-remove-line text-gray-400 hover:text-red-600 text-sm px-1" title="削除">×</button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 pt-3">
                <button type="button" class="tsn-add-line text-xs text-blue-600 hover:underline">+ もう1件追加</button>
                <div class="text-sm font-medium">
                    全体合計：<span class="tsn-grand-total">¥0</span>
                </div>
            </div>

            <div class="mt-4 text-right">
                <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">成果を反映する</button>
            </div>
        </form>
    </div>
@endif

<script>
function tsnBindAggregateLine(line) {
    var tsunaguPriceInput = line.querySelector('.tsn-tsunagu-price');
    var countInput = line.querySelector('.tsn-count-input');
    var lineTotalDisplay = line.querySelector('.tsn-line-total');

    function recalculate() {
        tsnRecalculateGrandTotal();

        var tsunaguPrice = parseInt(tsunaguPriceInput.value, 10);
        var count = parseInt(countInput.value, 10);

        if (isNaN(tsunaguPrice) || isNaN(count)) {
            lineTotalDisplay.textContent = '—';
            return;
        }

        lineTotalDisplay.textContent = '¥' + (tsunaguPrice * count).toLocaleString();
    }

    line.querySelectorAll('.tsn-tsunagu-price, .tsn-count-input').forEach(function (input) {
        input.addEventListener('input', recalculate);
    });

    recalculate();
}

function tsnRecalculateGrandTotal() {
    var grandTotal = 0;
    document.querySelectorAll('.tsn-line').forEach(function (line) {
        var tsunaguPrice = parseInt(line.querySelector('.tsn-tsunagu-price').value, 10);
        var count = parseInt(line.querySelector('.tsn-count-input').value, 10);
        if (!isNaN(tsunaguPrice) && !isNaN(count)) {
            grandTotal += tsunaguPrice * count;
        }
    });

    var grandTotalDisplay = document.querySelector('.tsn-grand-total');
    if (grandTotalDisplay) {
        grandTotalDisplay.textContent = '¥' + grandTotal.toLocaleString();
    }
}

document.querySelectorAll('.tsn-line').forEach(tsnBindAggregateLine);

var linesContainer = document.querySelector('.tsn-lines');
var addButton = document.querySelector('.tsn-add-line');

if (addButton) {
    addButton.addEventListener('click', function () {
        var lines = linesContainer.querySelectorAll('.tsn-line');
        var newIndex = lines.length;
        var template = lines[0].cloneNode(true);

        template.querySelectorAll('input, select').forEach(function (input) {
            input.name = input.name.replace(/lines\[\d+\]/, 'lines[' + newIndex + ']');
            if (input.classList.contains('tsn-commission-checkbox') || input.classList.contains('tsn-commission-hidden')) {
                return;
            }
            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else {
                input.value = input.classList.contains('tsn-count-input') ? '1' : '';
            }
        });
        template.querySelectorAll('.tsn-commission-checkbox').forEach(function (cb) {
            cb.checked = true;
        });
        template.querySelector('.tsn-line-total').textContent = '—';

        linesContainer.appendChild(template);
        tsnBindAggregateLine(template);
    });
}

document.addEventListener('click', function (e) {
    if (!e.target.classList.contains('tsn-remove-line')) {
        return;
    }
    var line = e.target.closest('.tsn-line');
    var container = line.parentElement;
    if (container.querySelectorAll('.tsn-line').length > 1) {
        line.remove();
        container.querySelectorAll('.tsn-line').forEach(function (l, i) {
            l.querySelectorAll('input, select').forEach(function (input) {
                input.name = input.name.replace(/lines\[\d+\]/, 'lines[' + i + ']');
            });
        });
        tsnRecalculateGrandTotal();
    }
});
</script>
@endsection
