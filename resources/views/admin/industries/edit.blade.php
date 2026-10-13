@extends('layouts.admin')

@section('title', '業種カテゴリー編集')

@section('content')
<h1 class="text-xl font-semibold mb-6">業種カテゴリー編集</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.industries.update', $industry) }}">
        @include('admin.industries._form')
    </form>
</div>
@endsection
