@extends('layouts.admin')

@section('title', '営業素材')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">営業素材（PDF）</h1>
    <a href="{{ route('admin.sales-materials.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">新規追加</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">タイトル</th>
                <th class="px-4 py-3 font-medium">ファイル</th>
                <th class="px-4 py-3 font-medium w-32"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($materials as $material)
                <tr>
                    <td class="px-4 py-3">{{ $material->title }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($material->file_path) }}" target="_blank" class="text-blue-600 hover:underline">PDFを見る</a>
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.sales-materials.edit', $material) }}" class="text-blue-600 hover:underline">編集</a>
                        <form method="POST" action="{{ route('admin.sales-materials.destroy', $material) }}" class="inline" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">削除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-gray-400">営業素材がありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
