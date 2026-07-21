@csrf
@if ($announcement->exists)
    @method('PUT')
@endif

<div class="mb-4">
    <label for="body" class="block text-sm font-medium text-gray-700 mb-1">内容</label>
    <textarea name="body" id="body" rows="3" required
              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('body', $announcement->body) }}</textarea>
    @error('body')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">分類</label>
    <select name="category" id="category" required
            class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @foreach (\App\Enums\AnnouncementCategory::cases() as $category)
            <option value="{{ $category->value }}" @selected(old('category', $announcement->category?->value ?? 'important') === $category->value)>{{ $category->label() }}</option>
        @endforeach
    </select>
</div>

<div class="mb-4 border border-gray-200 rounded-md p-4">
    <label class="flex items-center gap-2">
        <input type="checkbox" name="notify_line" id="notify_line" value="1" {{ old('notify_line', $announcement->notify_line) ? 'checked' : '' }}
               onchange="document.getElementById('line_message_wrap').classList.toggle('hidden', !this.checked)">
        <span class="text-sm font-medium text-gray-700">LINEでも通知する</span>
    </label>
    @if ($announcement->exists && $announcement->notify_line)
        <p class="text-xs text-gray-400 mt-1">※ このお知らせは新規作成時にLINE送信済みです。編集で再送信されることはありません。</p>
    @endif

    <div id="line_message_wrap" class="mt-3 {{ old('notify_line', $announcement->notify_line) ? '' : 'hidden' }}">
        <label for="line_message" class="block text-sm font-medium text-gray-700 mb-1">LINE通知文（未入力の場合は「内容」がそのまま使われます）</label>
        <textarea name="line_message" id="line_message" rows="3"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('line_message', $announcement->line_message) }}</textarea>
        @error('line_message')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex gap-3">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-500 px-4 py-2">キャンセル</a>
</div>
