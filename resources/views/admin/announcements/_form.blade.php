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

<div class="flex gap-3">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-500 px-4 py-2">キャンセル</a>
</div>
