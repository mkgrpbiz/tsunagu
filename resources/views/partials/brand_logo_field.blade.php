<div class="mb-4">
    <label for="brand_logo" class="block text-sm font-medium text-gray-700 mb-1">ロゴ画像（ホーム・LP共通）</label>
    <p class="text-xs text-gray-500 mb-2">アップロードすると「TSUNAGU Partner Network」の文字表示の代わりに画像が表示されます。</p>

    @if ($homeContent->brand_logo_path)
        <div class="flex items-center gap-3 mb-2">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($homeContent->brand_logo_path) }}" alt="" class="h-10">
            <label class="flex items-center gap-1.5 text-sm text-gray-600">
                <input type="checkbox" name="remove_brand_logo" value="1">
                画像を削除してテキスト表示に戻す
            </label>
        </div>
    @endif

    <input type="file" name="brand_logo" id="brand_logo" accept="image/*"
           class="w-full text-sm">
    @error('brand_logo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>
