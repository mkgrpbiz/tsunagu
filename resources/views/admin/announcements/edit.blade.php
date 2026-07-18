@extends('layouts.admin')

@section('title', 'お知らせ編集')

@section('content')
<h1 class="text-xl font-semibold mb-6">お知らせ編集</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
        @include('admin.announcements._form')
    </form>
</div>
@endsection
