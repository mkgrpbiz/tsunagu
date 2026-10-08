@extends('layouts.admin')

@section('title', '問い合わせ一覧')

@section('content')
<h1 class="text-xl font-semibold mb-6">問い合わせ一覧</h1>

@if (session('status'))
    <div class="mb-6 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mb-6 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
@endif

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
    <div>
        <label for="project_id" class="block text-xs font-medium text-gray-700 mb-1">案件で絞り込み</label>
        <select name="project_id" id="project_id" onchange="this.form.submit()" class="rounded-md border border-gray-300 text-sm">
            <option value="">すべての案件</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected($projectId == $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex-1 min-w-[200px]">
        <label for="q" class="block text-xs font-medium text-gray-700 mb-1">名前・フリガナ・LINE名・会員番号で検索</label>
        <div class="flex gap-2">
            <input type="text" name="q" id="q" value="{{ $q }}" class="flex-1 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">検索</button>
        </div>
    </div>
    @if ($projectId || $q !== '')
        <a href="{{ route('admin.inquiries.index', ['month' => $month ?? 'all']) }}" class="text-sm text-gray-500">絞り込み解除</a>
    @endif
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

<details class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <summary class="text-sm font-medium text-gray-700 cursor-pointer select-none">一括紐付け（スプレッドシートから貼り付け）</summary>
    <p class="text-xs text-gray-500 mt-3 mb-3">
        案件を選んだうえで、「名前 - フリガナ - TSUNAGU単価 - パートナー単価 - 件数（省略可、未入力は1件）」の順にタブ区切りで貼り付けてください。1行1件です。
    </p>
    <form method="POST" action="{{ route('admin.inquiries.link-bulk-preview') }}">
        @csrf
        <div class="mb-3">
            <label for="bulk_link_project_id" class="block text-sm font-medium text-gray-700 mb-1">案件</label>
            <select name="project_id" id="bulk_link_project_id" required
                    class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">選択してください</option>
                @foreach ($bulkLinkProjects as $bulkProject)
                    <option value="{{ $bulkProject->id }}">{{ $bulkProject->name }}</option>
                @endforeach
            </select>
        </div>
        <textarea name="pasted_text" rows="6" required placeholder="三浦小雪&#9;ミウラコユキ&#9;1000&#9;800&#9;3"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</details>

<div class="flex items-center justify-between mb-3">
    <div></div>
    <button type="button" onclick="document.getElementById('tsn-no-referral-form').classList.toggle('hidden')"
            class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md px-3 py-1.5">該当なし成果を追加</button>
</div>

<div id="tsn-no-referral-form" class="hidden bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <h3 class="text-sm font-medium text-gray-700 mb-1">該当なし成果を追加</h3>
    <p class="text-xs text-gray-500 mb-3">紹介元パートナーがいない成果（直接反響など）を、全額TSUNAGU利益として追加・紐付けします。</p>
    <form method="POST" action="{{ route('admin.inquiries.no-referral') }}" class="grid grid-cols-5 gap-3 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500 mb-1">案件</label>
            <select name="project_id" required class="w-full rounded-md border border-gray-300 text-sm">
                <option value="">選択してください</option>
                @foreach ($projects as $everyProjectItem)
                    <option value="{{ $everyProjectItem->id }}">{{ $everyProjectItem->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">名前</label>
            <input type="text" name="name" required class="w-full rounded-md border border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">フリガナ</label>
            <input type="text" name="name_kana" required class="w-full rounded-md border border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">TSUNAGU単価</label>
            <input type="number" name="tsunagu_unit_price" min="0" required class="w-full rounded-md border border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">件数</label>
            <input type="number" name="count" min="1" step="1" value="1" required class="w-full rounded-md border border-gray-300 text-sm">
        </div>
        <div class="col-span-5">
            <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">追加して紐付ける</button>
        </div>
    </form>
</div>

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

<div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
    <table class="w-full text-sm min-w-max">
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
                <th class="px-4 py-3 font-medium">着金</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($inquiries as $inquiry)
                @php
                    $contracts = $inquiry->contracts;
                    $isLinkable = $contracts->isEmpty() || $inquiry->project->is_recurring;
                    $tsunaguPrice = $inquiry->project->singleTsunaguUnitPrice();
                    $agencyPrice = $inquiry->project->singleAgencyUnitPrice();
                @endphp
                <tr class="even:bg-gray-50 hover:bg-gray-100 align-top">
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
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($contracts->isNotEmpty())
                            <span class="text-xs font-medium border rounded-full px-1.5 py-0.5 bg-amber-50 text-amber-700 border-amber-200">紐付け済み{{ $contracts->count() }}件</span>
                            <span class="block text-xs text-gray-500 mt-0.5">¥{{ number_format($contracts->sum('deposit_amount')) }}</span>
                        @endif
                        @if ($isLinkable)
                            <button type="button" onclick="document.getElementById('tsn-link-row-{{ $inquiry->id }}').classList.toggle('hidden')"
                                    class="text-xs text-blue-600 hover:underline mt-0.5">紐付け{{ $contracts->isNotEmpty() ? '追加' : '' }}</button>
                        @endif
                    </td>
                </tr>
                @if ($isLinkable)
                    <tr id="tsn-link-row-{{ $inquiry->id }}" class="hidden">
                        <td colspan="10" class="p-0 border-b border-gray-200">
                            <form id="tsn-link-form-{{ $inquiry->id }}" method="POST" action="{{ route('admin.inquiries.link', $inquiry) }}">
                                @csrf
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="hidden" name="project_id" value="{{ $projectId }}">
                                <input type="hidden" name="q" value="{{ $q }}">
                            </form>
                            <div class="p-4 bg-blue-50 tsn-deposit-row">
                                @if ($inquiry->siblingProjects->isNotEmpty())
                                    <div class="mb-2 text-sm">
                                        <span class="text-gray-500 text-xs">案件</span>
                                        <span class="tsn-project-label">{{ $inquiry->project->name }}</span>
                                        <button type="button" class="tsn-toggle-project-change ml-1 text-xs text-blue-600 hover:underline">変更</button>
                                        <div class="tsn-project-change hidden mt-1">
                                            <select class="tsn-project-select text-xs rounded-md border border-gray-300"
                                                    form="tsn-link-form-{{ $inquiry->id }}" name="override_project_id">
                                                <option value="" data-tsunagu-price="{{ $tsunaguPrice }}" data-agency-price="{{ $agencyPrice }}" data-name="{{ $inquiry->project->name }}">{{ $inquiry->project->name }}（元の案件）</option>
                                                @foreach ($inquiry->siblingProjects as $sibling)
                                                    <option value="{{ $sibling->id }}" data-tsunagu-price="{{ $sibling->singleTsunaguUnitPrice() }}" data-agency-price="{{ $sibling->singleAgencyUnitPrice() }}" data-name="{{ $sibling->name }}">{{ $sibling->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif
                                <div class="tsn-lines space-y-2 mb-2">
                                    <div class="grid grid-cols-8 gap-3 items-end text-sm tsn-line">
                                        <div>
                                            <span class="text-gray-400 text-xs block">TSUNAGU単価</span>
                                            <input type="number" name="lines[0][tsunagu_unit_price]" min="0" required
                                                   form="tsn-link-form-{{ $inquiry->id }}"
                                                   class="tsn-tsunagu-price w-24 rounded-md border border-gray-300 text-sm"
                                                   value="{{ $tsunaguPrice }}" placeholder="{{ $tsunaguPrice === null ? '金額' : '' }}">
                                        </div>
                                        <div>
                                            <span class="text-gray-400 text-xs block">パートナー単価</span>
                                            <input type="number" name="lines[0][agency_unit_price]" min="0" required
                                                   form="tsn-link-form-{{ $inquiry->id }}"
                                                   class="tsn-agency-price w-24 rounded-md border border-gray-300 text-sm"
                                                   value="{{ $agencyPrice }}" placeholder="{{ $agencyPrice === null ? '金額' : '' }}">
                                        </div>
                                        <div>
                                            <span class="text-gray-400 text-xs block">獲得経費(%)</span>
                                            <input type="number" name="lines[0][acquisition_cost_rate]" min="0" max="100" step="0.01"
                                                   form="tsn-link-form-{{ $inquiry->id }}"
                                                   class="tsn-acquisition-rate w-20 rounded-md border border-gray-300 text-sm"
                                                   placeholder="求人は30">
                                        </div>
                                        <div>
                                            <span class="text-gray-400 text-xs block">件数</span>
                                            <input type="number" name="lines[0][count]" min="1" step="1" value="1" required
                                                   form="tsn-link-form-{{ $inquiry->id }}"
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
                                    <button type="submit" form="tsn-link-form-{{ $inquiry->id }}" class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md px-3 py-1.5">紐付け</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td colspan="10" class="px-4 py-6 text-center text-gray-400">問い合わせはまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $inquiries->links() }}
</div>

<script>
function tsnBindLine(line) {
    var tsunaguPriceInput = line.querySelector('.tsn-tsunagu-price');
    var agencyPriceInput = line.querySelector('.tsn-agency-price');
    var acquisitionRateInput = line.querySelector('.tsn-acquisition-rate');
    var countInput = line.querySelector('.tsn-count-input');
    var tsunaguTotalInput = line.querySelector('.tsn-tsunagu-total');
    var agencyTotalInput = line.querySelector('.tsn-agency-total');
    var profitDisplay = line.querySelector('.tsn-profit-display');

    function recalculate() {
        var tsunaguPrice = parseInt(tsunaguPriceInput.value, 10);
        var agencyPrice = parseInt(agencyPriceInput.value, 10);
        var acquisitionRate = parseFloat(acquisitionRateInput.value);
        var count = parseInt(countInput.value, 10);

        if (isNaN(tsunaguPrice) || isNaN(agencyPrice) || isNaN(count)) {
            tsunaguTotalInput.value = '';
            agencyTotalInput.value = '';
            profitDisplay.textContent = '—';
            return;
        }

        if (isNaN(acquisitionRate)) {
            acquisitionRate = 0;
        }

        var tsunaguTotal = tsunaguPrice * count;
        var agencyTotal = agencyPrice * count;
        var acquisitionCost = Math.round(tsunaguTotal * acquisitionRate / 100);
        tsunaguTotalInput.value = tsunaguTotal;
        agencyTotalInput.value = agencyTotal;
        profitDisplay.textContent = '¥' + (tsunaguTotal - agencyTotal - acquisitionCost).toLocaleString();
    }

    [tsunaguPriceInput, agencyPriceInput, acquisitionRateInput, countInput].forEach(function (input) {
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
                if (input.classList.contains('tsn-count-input')) {
                    input.value = '1';
                } else {
                    input.value = '';
                }
            }
        });
        template.querySelector('.tsn-profit-display').textContent = '—';

        linesContainer.appendChild(template);
        tsnBindLine(template);
    });

    var toggleButton = row.querySelector('.tsn-toggle-project-change');
    var changeBox = row.querySelector('.tsn-project-change');
    var select = row.querySelector('.tsn-project-select');
    var projectLabel = row.querySelector('.tsn-project-label');

    if (!toggleButton) {
        return;
    }

    toggleButton.addEventListener('click', function () {
        changeBox.classList.toggle('hidden');
    });

    select.addEventListener('change', function () {
        var option = select.options[select.selectedIndex];
        projectLabel.textContent = option.dataset.name;

        var firstLine = linesContainer.querySelector('.tsn-line');
        var tsunaguInput = firstLine.querySelector('.tsn-tsunagu-price');
        var agencyInput = firstLine.querySelector('.tsn-agency-price');

        if (option.dataset.tsunaguPrice) {
            tsunaguInput.value = option.dataset.tsunaguPrice;
            tsunaguInput.dispatchEvent(new Event('input'));
        }
        if (option.dataset.agencyPrice) {
            agencyInput.value = option.dataset.agencyPrice;
            agencyInput.dispatchEvent(new Event('input'));
        }
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
