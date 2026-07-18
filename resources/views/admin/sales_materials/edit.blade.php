@extends('layouts.admin')

@section('title', '営業素材編集')

@section('content')
<h1 class="text-xl font-semibold mb-6">営業素材編集</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.sales-materials.update', $material) }}" enctype="multipart/form-data">
        @include('admin.sales_materials._form')
    </form>
</div>
@endsection
