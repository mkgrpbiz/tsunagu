@extends('layouts.admin')

@section('title', $type->label().' 編集')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">{{ $type->label() }} 編集</h1>
    <a href="{{ route('admin.legal-documents.history', $type->value) }}" class="text-sm text-blue-600 hover:underline">履歴を見る</a>
</div>

<p class="text-sm text-gray-500 mb-4">保存すると新しいバージョンとして追加されます（既存のバージョンは削除されず履歴に残ります）。</p>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-3xl">
    <form method="POST" action="{{ route('admin.legal-documents.update', $type->value) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">タイトル</label>
            <input type="text" name="title" id="title" value="{{ old('title', $document?->title) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('title')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="body" class="block text-sm font-medium text-gray-700 mb-1">本文</label>
            <textarea name="body" id="body" rows="16"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('body', $document?->body) }}</textarea>
            @error('body')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label for="version" class="block text-sm font-medium text-gray-700 mb-1">バージョン</label>
                <input type="text" name="version" id="version" value="{{ old('version', $document?->version) }}" required
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('version')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="effective_date" class="block text-sm font-medium text-gray-700 mb-1">施行日</label>
                <input type="date" name="effective_date" id="effective_date" value="{{ old('effective_date', $document?->effective_date?->format('Y-m-d')) }}" required
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('effective_date')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">公開状態</label>
                <select name="status" id="status" required
                        class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $document?->status?->value) == $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('status')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-6">
            <label for="change_notes" class="block text-sm font-medium text-gray-700 mb-1">更新内容メモ</label>
            <textarea name="change_notes" id="change_notes" rows="2"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('change_notes') }}</textarea>
            @error('change_notes')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
            <a href="{{ route('admin.legal-documents.index') }}" class="text-sm text-gray-500 px-4 py-2">キャンセル</a>
        </div>
    </form>
</div>
@endsection
