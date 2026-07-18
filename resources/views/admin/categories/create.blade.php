@extends('layouts.admin')

@section('title', 'カテゴリー作成')

@section('content')
<h1 class="text-xl font-semibold mb-6">カテゴリー作成</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.categories.store') }}">
        @include('admin.categories._form')
    </form>
</div>
@endsection
