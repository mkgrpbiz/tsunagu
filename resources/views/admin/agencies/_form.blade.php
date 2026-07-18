@csrf
@if ($agency->exists)
    @method('PUT')
@endif

<div class="grid grid-cols-2 gap-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前</label>
        <input type="text" name="name" id="name" value="{{ old('name', $agency->name) }}" required
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="name_kana" class="block text-sm font-medium text-gray-700 mb-1">フリガナ</label>
        <input type="text" name="name_kana" id="name_kana" value="{{ old('name_kana', $agency->name_kana) }}" required
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('name_kana')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">性別</label>
        <select name="gender" id="gender" required
                class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">選択してください</option>
            @foreach ($genders as $gender)
                <option value="{{ $gender->value }}" @selected(old('gender', $agency->gender?->value) == $gender->value)>{{ $gender->label() }}</option>
            @endforeach
        </select>
        @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="prefecture" class="block text-sm font-medium text-gray-700 mb-1">お住まい（都道府県）</label>
        <input type="text" name="prefecture" id="prefecture" value="{{ old('prefecture', $agency->prefecture) }}" required
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('prefecture')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">ご職業</label>
        <input type="text" name="occupation" id="occupation" value="{{ old('occupation', $agency->occupation) }}"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('occupation')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $agency->phone) }}" required
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス（ログインID）</label>
        <input type="email" name="email" id="email" value="{{ old('email', $agency->email) }}" required
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        @unless ($agency->exists)
            <p class="text-xs text-gray-500 mt-1">初期パスワードは pass1234 です。初回ログイン時に変更が必要になります。</p>
        @endunless
    </div>

    <div class="col-span-2 border-t border-gray-100 pt-4 mt-2">
        <p class="text-sm font-medium text-gray-700 mb-3">振込先情報</p>
    </div>

    <div>
        <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">銀行名</label>
        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $agency->bank_name) }}" autocomplete="off"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <input type="hidden" name="bank_code" id="bank_code" value="{{ old('bank_code', $agency->bank_code) }}">
        @error('bank_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="bank_branch_name" class="block text-sm font-medium text-gray-700 mb-1">支店名</label>
        <input type="text" name="bank_branch_name" id="bank_branch_name" value="{{ old('bank_branch_name', $agency->bank_branch_name) }}" autocomplete="off"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <input type="hidden" name="bank_branch_code" id="bank_branch_code" value="{{ old('bank_branch_code', $agency->bank_branch_code) }}">
        @error('bank_branch_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="bank_account_type" class="block text-sm font-medium text-gray-700 mb-1">口座種別</label>
        <select name="bank_account_type" id="bank_account_type"
                class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">選択してください</option>
            @foreach ($bankAccountTypes as $type)
                <option value="{{ $type->value }}" @selected(old('bank_account_type', $agency->bank_account_type?->value) == $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @error('bank_account_type')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">口座番号</label>
        <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $agency->bank_account_number) }}"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('bank_account_number')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="col-span-2">
        <label for="bank_account_holder" class="block text-sm font-medium text-gray-700 mb-1">口座名義（カナ）</label>
        <input type="text" name="bank_account_holder" id="bank_account_holder" value="{{ old('bank_account_holder', $agency->bank_account_holder) }}"
               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @error('bank_account_holder')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div class="flex gap-3 mt-6">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    <a href="{{ route('admin.agencies.index') }}" class="text-sm text-gray-500 px-4 py-2">キャンセル</a>
</div>
