@csrf
@if ($project->exists)
    @method('PUT')
@endif

<div class="grid grid-cols-2 gap-6">
    <div>
        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">カテゴリー</label>
        <select name="category_id" id="category_id" required
                class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">選択してください</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $project->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">ステータス</label>
        <select name="status" id="status" required
                class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $project->status?->value) == $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <input type="checkbox" name="is_recurring" value="1" @checked(old('is_recurring', $project->is_recurring))>
            ストック系案件（同じ問い合わせに何度でも着金紐付けできるようにする）
        </label>
        <p class="text-xs text-gray-500 mt-1">OFFの場合、1つの問い合わせに着金を紐付けると着金紐付け候補から消えます（誤って二重に紐付けるのを防止）。ONにすると何度でも紐付けでき、着金紐付け候補にも残り続けます。</p>
        @error('is_recurring')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <input type="checkbox" name="bulk_link_enabled" value="1" @checked(old('bulk_link_enabled', $project->bulk_link_enabled))>
            一括紐付け（貼り付け）の対象案件にする
        </label>
        <p class="text-xs text-gray-500 mt-1">ONにすると、着金紐付け画面の一括紐付け（貼り付け）の案件プルダウンに表示されます。</p>
        @error('bulk_link_enabled')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="relative">
        <label for="client_name" class="block text-sm font-medium text-gray-700 mb-1">共創パートナー名</label>
        <input type="text" name="client_name" id="client_name" value="{{ old('client_name', $project->client_name) }}"
               autocomplete="off"
               oninput="tsnFilterClientNameSuggestions(this.value)"
               onfocus="tsnFilterClientNameSuggestions(this.value)"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <div id="client_name_suggestions" class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto text-sm"></div>
        <input type="hidden" name="referrer_agency_id" id="referrer_agency_id" value="{{ old('referrer_agency_id', $project->referrer_agency_id) }}">
        <p class="text-xs text-gray-500 mt-1">
            紹介者: <span id="referrer_agency_label">{{ old('referrer_agency_id', $project->referrer_agency_id) ? $project->referrerAgency?->name : 'なし' }}</span>
            （会社名・登録コードで検索して共創パートナーの候補を選ぶと、そのパートナーを紹介した人が自動でセットされます）
        </p>
        @error('client_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        @error('referrer_agency_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">案件名</label>
        <input type="text" name="name" id="name" value="{{ old('name', $project->name) }}" required
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="legacy_names" class="block text-sm font-medium text-gray-700 mb-1">旧データ用案件名（任意）</label>
        <p class="text-xs text-gray-500 mb-1">過去のスプレッドシート等で使われていた別名がある場合、1行に1つずつ入力してください。問い合わせデータのインポート時に、この案件名と合わせてどちらでも本案件に紐づけられます。</p>
        <textarea name="legacy_names" id="legacy_names" rows="3"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('legacy_names', $project->legacy_names) }}</textarea>
        @error('legacy_names')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="image" class="block text-sm font-medium text-gray-700 mb-1">集客画像</label>
        @if ($project->image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($project->image_path) }}" alt="" class="h-24 mb-2 rounded-md border border-gray-200">
        @endif
        <input type="file" name="image" id="image" accept="image/*"
               class="w-full text-sm">
        @error('image')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    @php
        $tsunaguMode = old('tsunagu_price_mode', empty($project->tsunagu_unit_prices) ? 'variable' : 'fixed');
        $agencyMode = old('agency_price_mode', empty($project->agency_unit_prices) ? 'variable' : 'fixed');
        $tsunaguPrices = old('tsunagu_unit_price', $project->tsunagu_unit_prices ?: ['']);
        $agencyPrices = old('agency_unit_price', $project->agency_unit_prices ?: ['']);
    @endphp

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">TSUNAGU単価</label>
        <div class="flex items-center gap-4 mb-2 text-sm">
            <label class="flex items-center gap-1.5">
                <input type="radio" name="tsunagu_price_mode" value="fixed" class="price-mode-radio" data-target="tsunagu_unit_price_rows" @checked($tsunaguMode === 'fixed')>
                金額
            </label>
            <label class="flex items-center gap-1.5">
                <input type="radio" name="tsunagu_price_mode" value="variable" class="price-mode-radio" data-target="tsunagu_unit_price_rows" @checked($tsunaguMode === 'variable')>
                変動
            </label>
        </div>
        <div id="tsunagu_unit_price_rows" class="space-y-2">
            @foreach ($tsunaguPrices as $price)
                <div class="flex items-center gap-2 price-row">
                    <input type="number" name="tsunagu_unit_price[]" value="{{ $price }}" min="0" placeholder="円"
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" class="tsn-remove-price-row text-gray-400 hover:text-red-600 text-sm px-1" title="削除">×</button>
                </div>
            @endforeach
        </div>
        <button type="button" class="tsn-add-price-row text-xs text-blue-600 hover:underline mt-1" data-rows="tsunagu_unit_price_rows" data-name="tsunagu_unit_price[]">+ パターンを追加</button>
        @error('tsunagu_unit_price')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        @error('tsunagu_unit_price.*')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">パートナー単価</label>
        <div class="flex items-center gap-4 mb-2 text-sm">
            <label class="flex items-center gap-1.5">
                <input type="radio" name="agency_price_mode" value="fixed" class="price-mode-radio" data-target="agency_unit_price_rows" @checked($agencyMode === 'fixed')>
                金額
            </label>
            <label class="flex items-center gap-1.5">
                <input type="radio" name="agency_price_mode" value="variable" class="price-mode-radio" data-target="agency_unit_price_rows" @checked($agencyMode === 'variable')>
                変動
            </label>
        </div>
        <div id="agency_unit_price_rows" class="space-y-2">
            @foreach ($agencyPrices as $price)
                <div class="flex items-center gap-2 price-row">
                    <input type="number" name="agency_unit_price[]" value="{{ $price }}" min="0" placeholder="円"
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" class="tsn-remove-price-row text-gray-400 hover:text-red-600 text-sm px-1" title="削除">×</button>
                </div>
            @endforeach
        </div>
        <button type="button" class="tsn-add-price-row text-xs text-blue-600 hover:underline mt-1" data-rows="agency_unit_price_rows" data-name="agency_unit_price[]">+ パターンを追加</button>
        @error('agency_unit_price')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        @error('agency_unit_price.*')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">成果単価</label>
        <textarea name="description" id="description" rows="3"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $project->description) }}</textarea>
        @error('description')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="payment_timing" class="block text-sm font-medium text-gray-700 mb-1">着金タイミング</label>
        <input type="text" name="payment_timing" id="payment_timing" value="{{ old('payment_timing', $project->payment_timing) }}"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('payment_timing')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="sales_material" class="block text-sm font-medium text-gray-700 mb-1">営業資料（PDF）</label>
        @if ($project->sales_material_path)
            <p class="text-xs text-gray-500 mb-2">
                現在の資料:
                <a href="{{ \Illuminate\Support\Facades\Storage::url($project->sales_material_path) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">確認する</a>
            </p>
        @endif
        <input type="file" name="sales_material" id="sales_material" accept="application/pdf"
               class="w-full text-sm">
        @error('sales_material')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="recruitment_template" class="block text-sm font-medium text-gray-700 mb-1">案件概要</label>
        <textarea name="recruitment_template" id="recruitment_template" rows="4"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('recruitment_template', $project->recruitment_template) }}</textarea>
        @error('recruitment_template')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="line_auto_message" class="block text-sm font-medium text-gray-700 mb-1">LINE自動案内文</label>
        <textarea name="line_auto_message" id="line_auto_message" rows="4"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('line_auto_message', $project->line_auto_message) }}</textarea>
        @error('line_auto_message')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div class="flex gap-3 mt-6">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    <a href="{{ route('admin.projects.index') }}" class="text-sm text-gray-500 px-4 py-2">キャンセル</a>
</div>

<script>
var tsnPartnerDirectory = {{ Illuminate\Support\Js::from($agencies->map(fn ($a) => [
    'label' => $a->company_name ?: $a->name,
    'code' => $a->legacy_code,
    'referrerId' => $a->referred_by_agency_id,
    'referrerName' => $a->referredBy?->name,
])->values()) }};
var tsnClientHistory = {{ Illuminate\Support\Js::from($clientNames) }};

function tsnFilterClientNameSuggestions(query) {
    var box = document.getElementById('client_name_suggestions');
    var q = query.trim().toLowerCase();

    if (q === '') {
        box.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    var partnerMatches = tsnPartnerDirectory.filter(function (a) {
        return (a.label && a.label.toLowerCase().indexOf(q) !== -1) || (a.code && a.code.toLowerCase().indexOf(q) !== -1);
    });
    var partnerLabels = partnerMatches.map(function (a) { return a.label; });
    var historyMatches = tsnClientHistory.filter(function (name) {
        return name.toLowerCase().indexOf(q) !== -1 && partnerLabels.indexOf(name) === -1;
    });

    var items = partnerMatches.slice(0, 8).map(function (a) {
        return '<div class="px-3 py-2 hover:bg-blue-50 cursor-pointer tsn-client-suggestion" data-label="' + a.label.replace(/"/g, '&quot;') + '"'
            + ' data-referrer-id="' + (a.referrerId || '') + '" data-referrer-name="' + (a.referrerName ? a.referrerName.replace(/"/g, '&quot;') : '') + '" data-is-partner="1">'
            + a.label + (a.code ? ' <span class="text-gray-400">(' + a.code + ')</span>' : '') + '</div>';
    }).concat(historyMatches.slice(0, 8).map(function (name) {
        return '<div class="px-3 py-2 hover:bg-blue-50 cursor-pointer tsn-client-suggestion text-gray-600" data-label="' + name.replace(/"/g, '&quot;') + '">' + name + '</div>';
    }));

    if (items.length === 0) {
        box.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    box.innerHTML = items.join('');
    box.classList.remove('hidden');
}

document.addEventListener('click', function (e) {
    var suggestion = e.target.closest('.tsn-client-suggestion');
    if (suggestion) {
        document.getElementById('client_name').value = suggestion.dataset.label;
        if (suggestion.dataset.isPartner) {
            document.getElementById('referrer_agency_id').value = suggestion.dataset.referrerId || '';
            document.getElementById('referrer_agency_label').textContent = suggestion.dataset.referrerId ? suggestion.dataset.referrerName : 'なし';
        } else {
            document.getElementById('referrer_agency_id').value = '';
            document.getElementById('referrer_agency_label').textContent = 'なし';
        }
        document.getElementById('client_name_suggestions').classList.add('hidden');
        return;
    }
    if (!e.target.closest('#client_name') && !e.target.closest('#client_name_suggestions')) {
        document.getElementById('client_name_suggestions').classList.add('hidden');
    }
});

function tsnApplyPriceMode(radio) {
    var container = document.getElementById(radio.dataset.target);
    var isVariable = radio.value === 'variable';
    container.querySelectorAll('input').forEach(function (input) {
        input.disabled = isVariable;
        input.classList.toggle('bg-gray-100', isVariable);
        if (isVariable) {
            input.value = '';
        }
    });
}

document.querySelectorAll('.price-mode-radio').forEach(function (radio) {
    if (radio.checked) {
        tsnApplyPriceMode(radio);
    }
    radio.addEventListener('change', function () {
        tsnApplyPriceMode(radio);
    });
});

document.querySelectorAll('.tsn-add-price-row').forEach(function (button) {
    button.addEventListener('click', function () {
        var container = document.getElementById(button.dataset.rows);
        var row = document.createElement('div');
        row.className = 'flex items-center gap-2 price-row';
        row.innerHTML = '<input type="number" name="' + button.dataset.name + '" min="0" placeholder="円" class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">'
            + '<button type="button" class="tsn-remove-price-row text-gray-400 hover:text-red-600 text-sm px-1" title="削除">×</button>';
        container.appendChild(row);
    });
});

document.addEventListener('click', function (e) {
    if (!e.target.classList.contains('tsn-remove-price-row')) {
        return;
    }
    var row = e.target.closest('.price-row');
    var container = row.parentElement;
    if (container.querySelectorAll('.price-row').length > 1) {
        row.remove();
    } else {
        row.querySelector('input').value = '';
    }
});
</script>
