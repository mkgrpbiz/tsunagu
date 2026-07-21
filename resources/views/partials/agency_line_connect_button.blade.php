@php
    $connectAgency = $agency ?? auth('agency')->user();
    $connectToken = encrypt(['agency_id' => $connectAgency->id, 'expires_at' => now()->addMinutes(15)->timestamp]);
    $callbackFrom = '/agency/line-connection/callback?connect_token=' . urlencode($connectToken);
    $liffUrl = 'https://liff.line.me/' . ($liffId ?? config('services.line.liff_id')) . '?from=' . urlencode($callbackFrom);
@endphp

<a href="{{ $liffUrl }}" class="{{ $buttonClass ?? 'inline-block bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md px-6 py-2' }}">
    {{ $buttonLabel ?? 'LINE連携する' }}
</a>
