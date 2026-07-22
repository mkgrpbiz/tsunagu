@php
    $connectAgency = $agency ?? auth('agency')->user();
    $connectToken = \Illuminate\Support\Str::random(40);
    \Illuminate\Support\Facades\Cache::put(
        \App\Http\Controllers\Agency\LineConnectionController::cacheKey($connectToken),
        $connectAgency->id,
        now()->addMinutes(15),
    );
    $callbackFrom = '/agency/line-connection/callback?connect_token=' . $connectToken;
    $liffUrl = 'https://liff.line.me/' . ($liffId ?? config('services.line_partner.liff_id')) . '?from=' . urlencode($callbackFrom);
@endphp

<a href="{{ $liffUrl }}" class="{{ $buttonClass ?? 'inline-block bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md px-6 py-2' }}">
    {{ $buttonLabel ?? 'LINE連携する' }}
</a>
