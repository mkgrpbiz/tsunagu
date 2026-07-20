@extends('layouts.agency')

@section('title', '追加情報のご入力')

@section('content')
<h1 class="text-xl font-semibold mb-4">追加情報のご入力</h1>

@if ($isReconsent)
    <div class="mb-6 rounded-md bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-sm" style="white-space: pre-line">契約書類を更新しました。
引き続きTSUNAGUをご利用いただくため、変更内容をご確認のうえ、ご同意をお願いいたします。
ご同意いただくまで、案件一覧・紹介機能はご利用いただけません。</div>
@else
    <div class="mb-6 rounded-md bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
        サービスのご利用にあたり、契約書類へのご同意をお願いします。ご同意いただくまで、案件一覧・紹介機能はご利用いただけません。
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="bg-white border border-gray-200 rounded-lg p-6">
    <form method="POST" action="{{ route('agency.additional-info.update') }}">
        @csrf
        @method('PUT')

        <div class="space-y-2">
            @include('partials.legal_consent_checklist', ['legalDocuments' => $legalDocuments, 'typesNeedingConsent' => $typesNeedingConsent])
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">同意して次へ</button>
        </div>
    </form>
</div>
@endsection
