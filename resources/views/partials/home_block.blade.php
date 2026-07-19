@php
    $salesMaterials ??= collect();
    $announcements ??= collect();
    $restrictedReason ??= null;
    $restrictedMessages = [
        'pending_review' => ['審査中のため利用できません', '承認後にご利用いただけます。'],
        'consent_required' => ['契約書類へのご同意が必要です', '追加情報のご入力よりご同意いただくとご利用いただけます。'],
    ];
@endphp

@if ($block->type === 'text')
    <div class="card color-{{ $block->color ?? 'gray' }}">
        @if ($block->title)<div class="title">{{ $block->title }}</div>@endif
        <div class="body">{{ $block->body }}</div>
    </div>

@elseif ($block->type === 'benefits')
    @if ($block->title)<div class="benefits-title">{{ $block->title }}</div>@endif
    @foreach (explode("\n", trim((string) $block->body)) as $index => $line)
        @continue(trim($line) === '')
        @php [$itemTitle, $itemDesc] = array_pad(explode('|', $line, 2), 2, ''); @endphp
        <div class="benefit">
            <div class="num">{{ $index + 1 }}</div>
            <div>
                <div class="title">{{ trim($itemTitle) }}</div>
                <div class="desc">{{ trim($itemDesc) }}</div>
            </div>
        </div>
    @endforeach

@elseif ($block->type === 'image')
    <div class="image-block">
        @if ($block->image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($block->image_path) }}" alt="{{ $block->title }}">
        @endif
        @if ($block->title)<div class="caption">{{ $block->title }}</div>@endif
    </div>

@elseif ($block->type === 'cta')
    <div class="card">
        @if ($block->title)<div class="title">{{ $block->title }}</div>@endif
        @if ($block->body)<div class="body">{{ $block->body }}</div>@endif
        @if ($block->button_text && $block->button_url)
            <a href="{{ $block->button_url }}" class="cta-button" target="_blank" rel="noopener">{{ $block->button_text }}</a>
        @endif
    </div>

@elseif ($block->type === 'referral_cta')
    @if ($restrictedReason)
        @php [$restrictedTitle, $restrictedBody] = $restrictedMessages[$restrictedReason]; @endphp
        <div class="cta-card cta-card-restricted">
            <p class="restricted-title">{{ $restrictedTitle }}</p>
            <p class="restricted-body">{{ $restrictedBody }}</p>
        </div>
    @else
        <div class="cta-card">
            @if ($block->title)<div class="cta-title">{{ $block->title }}</div>@endif
            @if ($block->body)<div class="cta-body">{{ $block->body }}</div>@endif
            <input type="text" readonly value="{{ $referralUrl ?? url('/agency/register?ref=B0000') }}">
            <button type="button" class="copy" @if($agency ?? null) onclick="copyToClipboard({{ Illuminate\Support\Js::from($referralUrl) }})" @endif>紹介リンクをコピー</button>
        </div>
    @endif

@elseif ($block->type === 'collaboration_cta')
    @if ($restrictedReason)
        @php [$restrictedTitle, $restrictedBody] = $restrictedMessages[$restrictedReason]; @endphp
        <div class="cta-card cta-card-restricted">
            <p class="restricted-title">{{ $restrictedTitle }}</p>
            <p class="restricted-body">{{ $restrictedBody }}</p>
        </div>
    @else
        <div class="cta-card">
            @if ($block->title)<div class="cta-title">{{ $block->title }}</div>@endif
            @if ($block->body)<div class="cta-body">{{ $block->body }}</div>@endif
            <a href="{{ route('agency.collaboration-referrals.create') }}" class="cta-link">{{ $block->button_text ?: '共創先紹介フォーム' }}</a>
        </div>
    @endif

@elseif ($block->type === 'sales_materials')
    <div class="materials-title">{{ $block->title ?: '営業素材' }}</div>
    @forelse ($salesMaterials as $material)
        <div class="material-item">
            <span>{{ $material->title }}</span>
            <a href="{{ \Illuminate\Support\Facades\Storage::url($material->file_path) }}" target="_blank">PDFを見る</a>
        </div>
    @empty
        <p class="text-center text-sm text-gray-400 py-4">（現在、営業素材はありません）</p>
    @endforelse

@elseif ($block->type === 'announcements')
    <div class="ticker-card">
        <div class="ticker-head"><span class="ticker-dot"></span> {{ $block->title ?: '新着情報' }}</div>
        @if ($announcements->isEmpty())
            <p class="px-4 py-6 text-center text-sm text-gray-400">お知らせはまだありません。</p>
        @else
            <div class="ticker-viewport">
                <span class="ticker-fade top"></span>
                <span class="ticker-fade bottom"></span>
                <div class="ticker-track">
                    @for ($i = 0; $i < 2; $i++)
                        @foreach ($announcements as $announcement)
                            <div class="ticker-item">
                                <span class="date">{{ $announcement->created_at->format('n/j') }}</span>
                                <span>{{ $announcement->body }}</span>
                            </div>
                        @endforeach
                    @endfor
                </div>
            </div>
        @endif
    </div>
@endif
