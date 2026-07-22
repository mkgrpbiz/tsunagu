@csrf
@if ($block->exists)
    @method('PUT')
@endif

@php
    $typeLabels = [
        'text' => 'テキスト（背景色を選べます）',
        'image' => '画像',
        'benefits' => 'メリット一覧（番号付きカードで表示）',
        'cta' => 'CTA（ボタン付き）',
        'referral_cta' => 'パートナー紹介',
        'collaboration_partner_application_cta' => '共創パートナー申請',
        'sales_materials' => '営業素材（位置のみ）',
        'announcements' => 'お知らせ（位置のみ）',
    ];
@endphp

@if ($block->exists)
    <div class="mb-4">
        <span class="block text-sm font-medium text-gray-700 mb-1">種類</span>
        <p class="text-sm text-gray-600">{{ $typeLabels[$block->type] }}（作成後は種類を変更できません）</p>
    </div>
@else
    <div class="mb-4">
        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">種類</label>
        <select name="type" id="type" required class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(old('type') === $type)>{{ $typeLabels[$type] }}</option>
            @endforeach
        </select>
        @error('type')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>
@endif

<div class="mb-4">
    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">タイトル</label>
    <input type="text" name="title" id="title" value="{{ old('title', $block->title) }}"
           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    <p class="text-xs text-gray-500 mt-1">「営業素材」「お知らせ」ブロックでは見出しとして使われます（空欄でも可）。</p>
    @error('title')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-4">
    <label for="body" class="block text-sm font-medium text-gray-700 mb-1">本文</label>
    <textarea name="body" id="body" rows="4"
              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('body', $block->body) }}</textarea>
    <p class="text-xs text-gray-500 mt-1">
        テキスト・CTA・パートナー紹介・共創パートナー紹介・共創パートナー申請ブロックではそのまま本文として（説明文として）表示されます。<br>
        「メリット一覧」ブロックでは<strong>1行に1項目</strong>、<code>タイトル|説明</code>の形式で入力してください（例: 紹介導線を収益化|既にお持ちのSNS・コミュニティ・人脈などを活かして収益化できます。）。
    </p>
    @error('body')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-4">
    <label for="button_text" class="block text-sm font-medium text-gray-700 mb-1">ボタン文言</label>
    <input type="text" name="button_text" id="button_text" value="{{ old('button_text', $block->button_text) }}"
           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    <p class="text-xs text-gray-500 mt-1">「CTA」「共創パートナー紹介」「共創パートナー申請」ブロックのみ使用します（例: 詳しくはこちら）。</p>
    @error('button_text')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-4">
    <label for="button_url" class="block text-sm font-medium text-gray-700 mb-1">ボタンのリンク先URL</label>
    <input type="text" name="button_url" id="button_url" value="{{ old('button_url', $block->button_url) }}"
           placeholder="https://..."
           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    <p class="text-xs text-gray-500 mt-1">「CTA」ブロックのみ使用します。</p>
    @error('button_url')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-4">
    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">背景色</label>
    <select name="color" id="color" class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        @php
            $colorLabels = ['gray' => '通常（白）', 'blue' => '青', 'orange' => 'オレンジ（注意書き向け）', 'red' => '赤'];
        @endphp
        @foreach ($colors as $color)
            <option value="{{ $color }}" @selected(old('color', $block->color ?? 'gray') === $color)>{{ $colorLabels[$color] }}</option>
        @endforeach
    </select>
    <p class="text-xs text-gray-500 mt-1">「テキスト」ブロックのみ使用します。</p>
    @error('color')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="mb-4">
    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">画像</label>
    @if ($block->image_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($block->image_path) }}" alt="" class="h-24 mb-2 rounded-md border border-gray-200">
    @endif
    <input type="file" name="image" id="image" accept="image/*" class="w-full text-sm">
    <p class="text-xs text-gray-500 mt-1">「画像」ブロックのみ使用します。</p>
    @error('image')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="flex gap-3">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    <a href="{{ route('admin.home-blocks.index') }}" class="text-sm text-gray-500 px-4 py-2">キャンセル</a>
</div>
