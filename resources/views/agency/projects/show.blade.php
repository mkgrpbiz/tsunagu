@extends('layouts.agency')

@section('title', $project->name)

@push('styles')
<style>
.mk-case{margin:0;background:transparent;color:#111827;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans",Meiryo,sans-serif}
.mk-case *{box-sizing:border-box}
.mk-case .mk-wrap{max-width:760px;margin:0 auto;padding:0}
.mk-case .back-link{display:inline-flex;align-items:center;gap:4px;color:#2563eb;font-size:13px;font-weight:700;text-decoration:none;margin-bottom:14px}
.mk-case .hero{display:flex;align-items:center;gap:14px;background:linear-gradient(180deg,#eaf3ff 0%,#ffffff 100%);border:1px solid #dbeafe;border-radius:16px;box-shadow:0 16px 36px rgba(37,99,235,.12);padding:16px;margin-bottom:16px}
.mk-case .hero-thumb{width:72px;height:72px;border-radius:14px;overflow:hidden;flex-shrink:0;background:#eff6ff}
.mk-case .hero-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.mk-case .hero-title{font-weight:900;font-size:17px;line-height:1.4;color:#0f172a}
.mk-case .box{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:12px;margin:12px 0}
.mk-case .box-title{font-weight:800;margin:0 0 8px;display:flex;align-items:center;gap:8px}
.mk-case .box p{margin:0;font-size:14px;line-height:1.75;white-space:pre-line}
.mk-case .muted{margin-top:8px;color:#6b7280;font-size:12px;line-height:1.6}
.mk-case input[readonly]{border:1px solid #e5e7eb;border-radius:12px;padding:12px;font-size:13px;background:#f9fafb;color:#374151;width:100%}
.mk-case button.copy,.mk-case a.copy{border:none;border-radius:12px;padding:12px 14px;font-weight:800;cursor:pointer;background:#111827;color:#fff;width:100%;margin-top:8px;display:block;text-align:center;text-decoration:none;box-sizing:border-box}
.mk-case button.copy.copy-link{background:#2563eb}
.mk-case .copy-row{display:flex;gap:8px}
.mk-case .copy-row .copy{width:50%;margin-top:8px}
.mk-case .dl-btn{display:inline-flex;align-items:center;gap:6px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:999px;padding:8px 16px;font-weight:700;font-size:13px;text-decoration:none}
.mk-case .dl-btn:hover{background:#dbeafe}
.mk-case .dl-btn svg{width:16px;height:16px;flex-shrink:0}
.mk-case .mini-acc summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:8px;margin:0}
.mk-case .mini-acc summary::-webkit-details-marker{display:none}
.mk-case .mini-acc .mini-copy-btn{display:none;align-items:center;justify-content:center;width:22px;height:22px;border:none;border-radius:6px;background:#eff6ff;color:#2563eb;font-size:12px;cursor:pointer;flex-shrink:0}
.mk-case .mini-acc[open] summary .mini-copy-btn{display:inline-flex}
.mk-case .mini-acc .mini-chev{width:8px;height:8px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);transition:transform .18s ease;margin-left:auto;flex-shrink:0}
.mk-case .mini-acc[open] .mini-chev{transform:rotate(-135deg)}
.mk-case .mini-acc p{margin-top:8px}
</style>
@endpush

@section('content')
<div class="mk-case">
    <div class="mk-wrap">
        <a href="{{ route('agency.projects.index') }}" class="back-link">← 案件一覧に戻る</a>

        <div class="hero">
            <div class="hero-thumb">
                @if ($project->image_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($project->image_path) }}" alt="">
                @else
                    <img src="{{ asset('tsunagu-logo.png') }}" alt="">
                @endif
            </div>
            <div>
                <div class="hero-title">{{ $project->name }}</div>
            </div>
        </div>

        <div class="box">
            <p class="box-title">💰 成果単価</p>
            <p>{{ $project->description }}</p>
        </div>

        <div class="box">
            <p class="box-title">📅 着金タイミング</p>
            <p>{{ $project->payment_timing }}</p>
        </div>

        @if ($project->sales_material_path)
            <div class="box">
                <p class="box-title">📄 営業資料</p>
                <a href="{{ route('projects.sales-material.download', ['project' => $project, 'filename' => $project->sales_material_original_filename ?: $project->name.'.pdf']) }}" class="dl-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                    PDFをダウンロード
                </a>
            </div>
        @endif

        <div class="box">
            <details class="mini-acc">
                <summary class="box-title">
                    📝 案件概要
                    <button type="button" class="mini-copy-btn" title="コピー"
                            onclick="event.stopPropagation(); copyToClipboard({{ Illuminate\Support\Js::from($project->overviewText()) }})">📋</button>
                    <span class="mini-chev"></span>
                </summary>
                <p>{{ $project->overviewText() }}</p>
            </details>
        </div>

        <div class="box">
            <p class="box-title">📨 案内フォーム</p>
            <div class="muted">この案件専用の招待リンクです。お客様が送信するとLINEでご案内が届きます。</div>

            <input type="text" readonly value="{{ $inviteUrl }}">
            <div class="copy-row">
                <button type="button" class="copy copy-link"
                        onclick="copyToClipboard({{ Illuminate\Support\Js::from($inviteUrl) }})">
                    リンクをコピー
                </button>
                <a href="{{ $inviteUrl }}" target="_blank" rel="noopener" class="copy">
                    フォームを確認
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
