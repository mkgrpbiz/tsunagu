@php
    $connectAgency = $agency ?? auth('agency')->user();
    $state = encrypt(['agency_id' => $connectAgency->id, 'expires_at' => now()->addMinutes(15)->timestamp]);
    $redirectUri = route('agency.line-connection.oauth-callback');
    $lineLoginUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => config('services.line_partner.channel_id'),
        'redirect_uri' => $redirectUri,
        'state' => $state,
        'scope' => 'profile openid',
    ]);
@endphp

<a href="{{ $lineLoginUrl }}" class="{{ $buttonClass ?? 'inline-block bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md px-6 py-2' }}">
    {{ $buttonLabel ?? 'LINE連携する' }}
</a>
