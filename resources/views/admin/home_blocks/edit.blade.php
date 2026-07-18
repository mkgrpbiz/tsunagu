@extends('layouts.admin')

@section('title', 'ブロック編集')

@section('content')
<h1 class="text-xl font-semibold mb-6">ブロック編集</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.home-blocks.update', $block) }}" enctype="multipart/form-data">
        @include('admin.home_blocks._form')
    </form>
</div>
@endsection
