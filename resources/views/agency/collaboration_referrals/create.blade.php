@extends('layouts.agency')

@section('title', '共創先紹介フォーム')

@section('content')
<h1 class="text-xl font-semibold mb-6">共創先紹介フォーム</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <div class="rounded-md bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-sm leading-relaxed mb-6">
        TSUNAGUでは、共創につながる可能性のある企業・事業者をご紹介いただけます。<br>
        ご紹介いただいた内容をもとに確認・審査を行い、条件に合致した場合はTSUNAGUよりご連絡させていただきます。
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('agency.collaboration-referrals.store') }}" class="space-y-6">
        @csrf

        <div>
            <h2 class="text-sm font-semibold text-gray-700 mb-3">紹介者情報</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">紹介者名</label>
                    <input type="text" value="{{ $agency->name }}" readonly disabled
                           class="w-full rounded-md border border-gray-300 bg-gray-50 text-gray-500 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">パートナーコード</label>
                    <input type="text" value="{{ $agency->referral_code }}" readonly disabled
                           class="w-full rounded-md border border-gray-300 bg-gray-50 text-gray-500 shadow-sm">
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-gray-700 mb-3">紹介先情報</h2>
            <div class="space-y-4">
                <div>
                    <label for="referred_name" class="block text-sm font-medium text-gray-700 mb-1">紹介したい方のお名前</label>
                    <input type="text" name="referred_name" id="referred_name" value="{{ old('referred_name') }}" required
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="referred_company" class="block text-sm font-medium text-gray-700 mb-1">会社名（任意）</label>
                    <input type="text" name="referred_company" id="referred_company" value="{{ old('referred_company') }}"
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="referred_business" class="block text-sm font-medium text-gray-700 mb-1">現在されている事業・職業</label>
                    <textarea name="referred_business" id="referred_business" rows="3" required
                              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('referred_business') }}</textarea>
                </div>

                <div>
                    <label for="referred_track_record" class="block text-sm font-medium text-gray-700 mb-1">過去の取引実績・実績</label>
                    <textarea name="referred_track_record" id="referred_track_record" rows="3" required
                              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('referred_track_record') }}</textarea>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">紹介理由（なぜTSUNAGUと相性が良いと思ったか）</label>
                    <textarea name="reason" id="reason" rows="3" required
                              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('reason') }}</textarea>
                </div>

                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-1">ご本人へ紹介の了承は得ていますか？</span>
                    <div class="flex gap-6 mt-1">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="consent_obtained" value="1" required @checked(old('consent_obtained') === '1')>
                            はい
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="consent_obtained" value="0" required @checked(old('consent_obtained') === '0')>
                            いいえ
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-md bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-xs leading-relaxed">
            こちらのご紹介は審査制となります。<br>
            ご紹介内容を確認・審査のうえ、必要に応じてTSUNAGUよりLINEにてご連絡させていただきます。<br>
            ※ご紹介内容によっては、ご対応できない場合がございますので、あらかじめご了承ください。
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md py-2">
            紹介する
        </button>
    </form>
</div>
@endsection
