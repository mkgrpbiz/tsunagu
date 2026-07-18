@extends('layouts.admin')

@section('title', 'パートナー作成')

@section('content')
<h1 class="text-xl font-semibold mb-6">パートナー作成</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6">
    <form method="POST" action="{{ route('admin.agencies.store') }}">
        @include('admin.agencies._form')
    </form>
</div>
@endsection
