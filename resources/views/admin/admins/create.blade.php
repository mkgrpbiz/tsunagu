@extends('layouts.admin')

@section('title', '管理者追加')

@section('content')
<h1 class="text-xl font-semibold mb-6">管理者追加</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.admins.store') }}">
        @include('admin.admins._form', ['admin' => new App\Models\User])
    </form>
</div>
@endsection
