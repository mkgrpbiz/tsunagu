@php
    $unit = $unit ?? '';
    $tickCount = 4;
    $chartUid = 'chart-'.uniqid();

    $pointList = collect($points)->values();
    $n = $pointList->count();

    $max = collect($series)->flatMap(fn ($s) => $pointList->pluck($s['key']))->push(1)->max();
    $formatTick = fn ($value) => $unit.number_format((int) round($value));
    $maxLabelWidth = strlen($formatTick($max));

    $width = 640;
    $height = 240;
    $padLeft = max(36, 14 + $maxLabelWidth * 6);
    $padRight = 16;
    $padTop = 16;
    $padBottom = 46;
    $plotWidth = $width - $padLeft - $padRight;
    $plotHeight = $height - $padTop - $padBottom;

    $stepX = $n > 1 ? $plotWidth / ($n - 1) : 0;

    $toXY = function (int $index, $value) use ($padLeft, $stepX, $height, $padBottom, $plotHeight, $max) {
        $x = $padLeft + $stepX * $index;
        $y = $height - $padBottom - ($max > 0 ? ($value / $max) * $plotHeight : 0);

        return [round($x, 1), round($y, 1)];
    };

    $tooltipFor = fn ($p) => $p['month'].' ・ '.collect($series)->map(fn ($s) => $s['label'].': '.$unit.number_format((int) $p[$s['key']]))->implode(' ・ ');
@endphp

<div class="relative" id="{{ $chartUid }}">
<svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-auto">
    @for ($tick = 0; $tick <= $tickCount; $tick++)
        @php
            $tickValue = $max * $tick / $tickCount;
            $tickY = $height - $padBottom - ($plotHeight * $tick / $tickCount);
        @endphp
        <line x1="{{ $padLeft }}" y1="{{ $tickY }}" x2="{{ $width - $padRight }}" y2="{{ $tickY }}" stroke="#f1f5f9" stroke-width="1" />
        <text x="{{ $padLeft - 6 }}" y="{{ $tickY }}" font-size="9" fill="#9ca3af" text-anchor="end" dominant-baseline="middle">{{ $formatTick($tickValue) }}</text>
    @endfor

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

    @foreach ($pointList as $i => $p)
        @php
            $half = $stepX > 0 ? $stepX / 2 : $plotWidth / 2;
            $hitX = max($padLeft, $padLeft + $stepX * $i - $half);
            $hitW = $stepX > 0 ? $stepX : $plotWidth;
        @endphp
        <rect x="{{ round($hitX, 1) }}" y="{{ $padTop }}" width="{{ round($hitW, 1) }}" height="{{ $plotHeight }}"
              fill="transparent" class="chart-hit" data-tooltip="{{ $tooltipFor($p) }}"></rect>
    @endforeach
</svg>
<div class="chart-tooltip hidden absolute z-10 pointer-events-none bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap"></div>
</div>
<div class="flex gap-4 mt-2 text-xs text-gray-500">
    @foreach ($series as $s)
        <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full" style="background:{{ $s['color'] }}"></span>{{ $s['label'] }}</span>
    @endforeach
</div>

<script>
(function () {
    const container = document.getElementById('{{ $chartUid }}');
    const tooltip = container.querySelector('.chart-tooltip');

    container.querySelectorAll('.chart-hit').forEach((hit) => {
        hit.addEventListener('mouseenter', () => {
            tooltip.textContent = hit.dataset.tooltip;
            tooltip.classList.remove('hidden');
        });
        hit.addEventListener('mousemove', (e) => {
            const bounds = container.getBoundingClientRect();
            tooltip.style.left = (e.clientX - bounds.left + 12) + 'px';
            tooltip.style.top = (e.clientY - bounds.top - 32) + 'px';
        });
        hit.addEventListener('mouseleave', () => {
            tooltip.classList.add('hidden');
        });
    });
})();
</script>
