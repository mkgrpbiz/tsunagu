@php
    $width = 640;
    $height = 240;
    $padLeft = 36;
    $padRight = 16;
    $padTop = 16;
    $padBottom = 46;
    $plotWidth = $width - $padLeft - $padRight;
    $plotHeight = $height - $padTop - $padBottom;

    $pointList = collect($points)->values();
    $n = $pointList->count();
    $stepX = $n > 1 ? $plotWidth / ($n - 1) : 0;

    $max = collect($series)->flatMap(fn ($s) => $pointList->pluck($s['key']))->push(1)->max();

    $toXY = function (int $index, $value) use ($padLeft, $stepX, $height, $padBottom, $plotHeight, $max) {
        $x = $padLeft + $stepX * $index;
        $y = $height - $padBottom - ($max > 0 ? ($value / $max) * $plotHeight : 0);

        return [round($x, 1), round($y, 1)];
    };
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-auto">
    <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}" y2="{{ $height - $padBottom }}" stroke="#e5e7eb" stroke-width="1" />
    <line x1="{{ $padLeft }}" y1="{{ $height - $padBottom }}" x2="{{ $width - $padRight }}" y2="{{ $height - $padBottom }}" stroke="#e5e7eb" stroke-width="1" />

    @foreach ($series as $s)
        @php
            $polyPoints = $pointList->map(fn ($p, $i) => implode(',', $toXY($i, $p[$s['key']])))->implode(' ');
        @endphp
        <polyline points="{{ $polyPoints }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
        @foreach ($pointList as $i => $p)
            @php [$cx, $cy] = $toXY($i, $p[$s['key']]); @endphp
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3" fill="{{ $s['color'] }}" />
        @endforeach
    @endforeach

    @foreach ($pointList as $i => $p)
        @php $x = round($padLeft + $stepX * $i, 1); @endphp
        <text x="{{ $x }}" y="{{ $height - $padBottom + 10 }}" font-size="9" fill="#9ca3af" text-anchor="end" transform="rotate(-45 {{ $x }} {{ $height - $padBottom + 10 }})">{{ $p['month'] }}</text>
    @endforeach
</svg>
<div class="flex gap-4 mt-2 text-xs text-gray-500">
    @foreach ($series as $s)
        <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full" style="background:{{ $s['color'] }}"></span>{{ $s['label'] }}</span>
    @endforeach
</div>
