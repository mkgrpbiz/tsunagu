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
            <input type="checkbox" name="oshigoto_listed" value="1" @checked(old('oshigoto_listed', $project->oshigoto_listed))>
            おしごとナビに掲載する
        </label>
        <p class="text-xs text-gray-500 mt-1">ONにすると、下の「集客画像」「募集文テンプレ」の内容がおしごとナビ（全案件まとめページ）にそのまま表示されます。</p>
        @error('oshigoto_listed')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="client_name" class="block text-sm font-medium text-gray-700 mb-1">取引先名</label>
        <input type="text" name="client_name" id="client_name" value="{{ old('client_name', $project->client_name) }}"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('client_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="referrer_agency_id" class="block text-sm font-medium text-gray-700 mb-1">紹介者（共創パートナー）</label>
        <input type="text" id="referrer_agency_search" placeholder="共創パートナー名で検索"
               oninput="tsnFilterReferrerAgencyOptions(this.value)"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-1">
        <select name="referrer_agency_id" id="referrer_agency_id"
                class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">なし</option>
            @foreach ($agencies as $agency)
                <option value="{{ $agency->id }}" @selected(old('referrer_agency_id', $project->referrer_agency_id) == $agency->id)>{{ $agency->name }}</option>
            @endforeach
        </select>
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
        $tsunaguMode = old('tsunagu_price_mode', $project->tsunagu_unit_price === null ? 'variable' : 'fixed');
        $agencyMode = old('agency_price_mode', $project->agency_unit_price === null ? 'variable' : 'fixed');
    @endphp

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">TSUNAGU単価</label>
        <div class="flex items-center gap-4 mb-2 text-sm">
            <label class="flex items-center gap-1.5">
                <input type="radio" name="tsunagu_price_mode" value="fixed" class="price-mode-radio" data-target="tsunagu_unit_price" @checked($tsunaguMode === 'fixed')>
                金額
            </label>
            <label class="flex items-center gap-1.5">
                <input type="radio" name="tsunagu_price_mode" value="variable" class="price-mode-radio" data-target="tsunagu_unit_price" @checked($tsunaguMode === 'variable')>
                変動
            </label>
        </div>
        <input type="number" name="tsunagu_unit_price" id="tsunagu_unit_price" value="{{ old('tsunagu_unit_price', $project->tsunagu_unit_price) }}" min="0"
               placeholder="円"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('tsunagu_unit_price')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">パートナー単価</label>
        <div class="flex items-center gap-4 mb-2 text-sm">
            <label class="flex items-center gap-1.5">
                <input type="radio" name="agency_price_mode" value="fixed" class="price-mode-radio" data-target="agency_unit_price" @checked($agencyMode === 'fixed')>
                金額
            </label>
            <label class="flex items-center gap-1.5">
                <input type="radio" name="agency_price_mode" value="variable" class="price-mode-radio" data-target="agency_unit_price" @checked($agencyMode === 'variable')>
                変動
            </label>
        </div>
        <input type="number" name="agency_unit_price" id="agency_unit_price" value="{{ old('agency_unit_price', $project->agency_unit_price) }}" min="0"
               placeholder="円"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('agency_unit_price')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">紹介報酬</label>
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
        <label for="recruitment_template" class="block text-sm font-medium text-gray-700 mb-1">募集文テンプレ（招待リンクはパートナーマイページで自動挿入されます）</label>
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
function tsnFilterReferrerAgencyOptions(query) {
    var select = document.getElementById('referrer_agency_id');
    var q = query.trim();

    Array.from(select.options).forEach(function (option) {
        if (!option.value) {
            option.hidden = false;
            return;
        }
        option.hidden = q !== '' && option.textContent.indexOf(q) === -1;
    });
}

function tsnApplyPriceMode(radio) {
    var input = document.getElementById(radio.dataset.target);
    var isVariable = radio.value === 'variable';
    input.disabled = isVariable;
    input.classList.toggle('bg-gray-100', isVariable);
    if (isVariable) {
        input.value = '';
    }
}

document.querySelectorAll('.price-mode-radio').forEach(function (radio) {
    if (radio.checked) {
        tsnApplyPriceMode(radio);
    }
    radio.addEventListener('change', function () {
        tsnApplyPriceMode(radio);
    });
});
</script>
