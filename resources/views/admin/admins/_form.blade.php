@csrf
@if ($admin->exists)
    @method('PATCH')
@endif

<div class="mb-4">
    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前</label>
    <input type="text" name="name" id="name" value="{{ old('name', $admin->name) }}" required
           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-4">
    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
    <input type="email" name="email" id="email" value="{{ old('email', $admin->email) }}" required
           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-4">
    <span class="block text-sm font-medium text-gray-700 mb-2">役割</span>
    <div class="flex gap-6">
        <label class="flex items-center gap-2 text-sm">
            <input type="radio" name="role" value="admin" onchange="toggleMenuSelect(this.value)"
                   @checked(old('role', $admin->role ?? 'admin') === 'admin')>
            管理者（全メニュー閲覧可）
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="radio" name="role" value="operator" onchange="toggleMenuSelect(this.value)"
                   @checked(old('role', $admin->role ?? 'admin') === 'operator')>
            運用担当（メニュー選択）
        </label>
    </div>
    @error('role')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

@php $currentMenus = old('accessible_menus', $admin->accessible_menus ?? []); @endphp
<div id="menuSelectArea" class="mb-4 {{ old('role', $admin->role ?? 'admin') === 'operator' ? '' : 'hidden' }}">
    <span class="block text-sm font-medium text-gray-700 mb-2">閲覧可能メニュー</span>
    <div class="grid grid-cols-3 gap-2 border border-gray-200 rounded-md p-4">
        @foreach ($menuKeys as $key => $label)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="accessible_menus[]" value="{{ $key }}" @checked(in_array($key, $currentMenus))>
                {{ $label }}
            </label>
        @endforeach
    </div>
</div>

@unless ($admin->exists)
    <p class="text-xs text-gray-500 mb-4">初期パスワードは <span class="font-mono font-semibold">pass1234</span> です。</p>
@else
    <div class="mb-4 border-t border-gray-100 pt-4">
        <p class="text-sm font-medium text-gray-700 mb-2">パスワード変更（変更する場合のみ入力）</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">新しいパスワード</label>
                <input type="password" name="password" id="password"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">新しいパスワード（確認）</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>
    </div>
@endunless

<div class="flex gap-3">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">
        {{ $admin->exists ? '更新' : '追加' }}
    </button>
    <a href="{{ route('admin.admins.index') }}" class="text-sm text-gray-500 px-4 py-2">キャンセル</a>
</div>

<script>
function toggleMenuSelect(role) {
    document.getElementById('menuSelectArea').classList.toggle('hidden', role !== 'operator');
}
</script>
