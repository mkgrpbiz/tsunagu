@csrf
@if ($material->exists)
    @method('PUT')
@endif

<div class="mb-4">
    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">タイトル</label>
    <input type="text" name="title" id="title" value="{{ old('title', $material->title) }}" required
           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @error('title')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-4">
    <label for="file" class="block text-sm font-medium text-gray-700 mb-1">PDFファイル</label>
    @if ($material->exists)
        <p class="text-xs text-gray-500 mb-1">
            現在のファイル: <a href="{{ \Illuminate\Support\Facades\Storage::url($material->file_path) }}" target="_blank" class="text-blue-600 hover:underline">PDFを見る</a>（差し替える場合のみ選択してください）
        </p>
    @endif
    <input type="file" name="file" id="file" accept="application/pdf" @unless($material->exists) required @endunless class="w-full text-sm">
    @error('file')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="flex gap-3">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    <a href="{{ route('admin.sales-materials.index') }}" class="text-sm text-gray-500 px-4 py-2">キャンセル</a>
</div>
