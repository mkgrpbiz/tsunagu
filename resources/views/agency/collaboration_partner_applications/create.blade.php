@extends('layouts.agency')

@section('title', '共創パートナー申請')

@section('content')
<h1 class="text-xl font-semibold mb-4">共創パートナー申請</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <div class="rounded-md bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-sm leading-relaxed mb-6">
        TSUNAGUでは、通常のパートナー活動に加え、共同事業や新サービスの開発など、継続的な共創を行うパートナーを募集しています。<br>
        現在のパートナー情報および申請内容をもとに審査を行いますので、共創内容についてご入力ください。
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('agency.collaboration-partner-applications.store') }}" class="space-y-6">
        @csrf

        <div>
            <label for="collaboration_content" class="block text-sm font-medium text-gray-700 mb-1">共創したい内容</label>
            <p class="text-xs text-gray-500 mb-2">
                TSUNAGUとどのような共創を希望されますか？<br>
                例）新サービスを共同開発したい／自社サービスをTSUNAGUへ掲載したい／新しい案件を共同で展開したい／共同事業を立ち上げたい
            </p>
            <textarea name="collaboration_content" id="collaboration_content" rows="4" required
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('collaboration_content') }}</textarea>
        </div>

        <div>
            <label for="proposal_details" class="block text-sm font-medium text-gray-700 mb-1">具体的な提案内容</label>
            <p class="text-xs text-gray-500 mb-2">実現したい内容や進め方について、できるだけ具体的にご記入ください。</p>
            <textarea name="proposal_details" id="proposal_details" rows="4" required
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('proposal_details') }}</textarea>
        </div>

        <div>
            <label for="expected_role" class="block text-sm font-medium text-gray-700 mb-1">期待する役割・協力内容</label>
            <p class="text-xs text-gray-500 mb-2">TSUNAGUに期待することや、ご自身が担える役割についてご記入ください。</p>
            <textarea name="expected_role" id="expected_role" rows="4" required
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('expected_role') }}</textarea>
        </div>

        <div>
            <label for="reference_url" class="block text-sm font-medium text-gray-700 mb-1">参考資料・URL（任意）</label>
            <p class="text-xs text-gray-500 mb-2">ホームページ、サービスページ、SNS、提案資料などがございましたらご記入ください。</p>
            <input type="text" name="reference_url" id="reference_url" value="{{ old('reference_url') }}"
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div class="rounded-md bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-xs leading-relaxed">
            こちらの申請は審査制となります。<br>
            ご登録いただいているプロフィールおよび申請内容をもとに審査を行い、審査通過後はZoomでのお打ち合わせを実施させていただきます。<br>
            ※申請内容によっては、ご希望に添えない場合がございますので、あらかじめご了承ください。
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md py-2">
            共創パートナー申請をする
        </button>
    </form>
</div>
@endsection
