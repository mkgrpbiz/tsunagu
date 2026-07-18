@extends('layouts.admin')

@section('title', '案件編集')

@section('content')
<h1 class="text-xl font-semibold mb-6">案件編集</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6">
    <form method="POST" action="{{ route('admin.projects.update', $project) }}" enctype="multipart/form-data">
        @include('admin.projects._form')
    </form>
</div>
@endsection
