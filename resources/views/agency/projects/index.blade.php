@extends('layouts.agency')

@section('title', '案件一覧')

@push('styles')
<style>
.mk-cases{margin:0;background:transparent;color:#111827;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans",Meiryo,sans-serif}
.mk-cases *{box-sizing:border-box}
.mk-cases .mk-wrap{max-width:760px;margin:0 auto;padding:0}
.mk-cases .mk-title{border-radius:22px;padding:18px 18px 16px;margin:0 0 14px}
.mk-cases .blue-card{background:linear-gradient(180deg,#eaf3ff 0%,#ffffff 100%);border:1px solid #dbeafe;box-shadow:0 16px 36px rgba(37,99,235,.12)}
.mk-cases .mk-pill{display:inline-block;padding:6px 12px;margin-bottom:10px;border-radius:999px;background:#dbeafe;color:#2563eb;font-size:12px;font-weight:800}
.mk-cases .mk-title-main{font-weight:900;font-size:16px;line-height:1.35;color:#0f172a}
.mk-cases .mk-title-sub{margin-top:6px;font-size:12.5px;line-height:1.6;color:#475569}
.mk-cases details.case{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 24px rgba(0,0,0,.06);overflow:hidden;margin:10px 0}
.mk-cases summary{list-style:none;cursor:pointer;padding:14px;font-weight:800;display:flex;align-items:center;justify-content:space-between;gap:10px;background:linear-gradient(180deg,#eff6ff,#fff);border-bottom:1px solid #e5e7eb}
.mk-cases summary::-webkit-details-marker{display:none}
.mk-cases .chev{width:10px;height:10px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);transition:transform .18s ease;margin-left:auto;flex-shrink:0}
.mk-cases details.case[open] .chev{transform:rotate(-135deg)}
.mk-cases .body{padding:12px}
.mk-cases .box{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:12px;margin:12px 0}
.mk-cases .box-title{font-weight:800;margin:0 0 8px;display:flex;align-items:center;gap:8px}
.mk-cases .box p{margin:0;font-size:14px;line-height:1.75;white-space:pre-line}
.mk-cases .muted{margin-top:8px;color:#6b7280;font-size:12px;line-height:1.6}
.mk-cases input[readonly]{border:1px solid #e5e7eb;border-radius:12px;padding:12px;font-size:13px;background:#f9fafb;color:#374151;width:100%}
.mk-cases button.copy,.mk-cases a.copy{border:none;border-radius:12px;padding:12px 14px;font-weight:800;cursor:pointer;background:#111827;color:#fff;width:100%;margin-top:8px;display:block;text-align:center;text-decoration:none;box-sizing:border-box}
.mk-cases button.copy.copy-link{background:#2563eb}
.mk-cases .copy-row{display:flex;gap:8px}
.mk-cases .copy-row .copy{width:50%;margin-top:8px}
.mk-cases .mk-cat{background:transparent;border:none;border-left:4px solid #ea580c;padding:8px 12px;margin:28px 0 10px}
.mk-cases .mk-cat:first-of-type{margin-top:8px}
.mk-cases .mk-cat-title{color:#9a3412;font-size:15px;font-weight:800;margin-bottom:4px}
.mk-cases .dl-btn{display:inline-flex;align-items:center;gap:6px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:999px;padding:8px 16px;font-weight:700;font-size:13px;text-decoration:none}
.mk-cases .dl-btn:hover{background:#dbeafe}
.mk-cases .dl-btn svg{width:16px;height:16px;flex-shrink:0}
.mk-cases .mini-acc summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:8px;margin:0}
.mk-cases .mini-acc summary::-webkit-details-marker{display:none}
.mk-cases .mini-acc .mini-chev{width:8px;height:8px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);transition:transform .18s ease;margin-left:auto;flex-shrink:0}
.mk-cases .mini-acc[open] .mini-chev{transform:rotate(-135deg)}
.mk-cases .mini-acc p{margin-top:8px}
</style>
@endpush

@section('content')
<div class="mk-cases" id="mkCases">
    <div class="mk-wrap">
        @forelse ($projectsByCategory as $categoryName => $projects)
            <div class="mk-cat">
                <div class="mk-cat-title">{{ $categoryName }}</div>
            </div>

            @foreach ($projects as $project)
                <details class="case">
                    <summary>
                        {{ $project->name }}
                        <span class="chev"></span>
                    </summary>
                    <div class="body">
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
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($project->sales_material_path) }}" download="{{ $project->sales_material_original_filename ?: $project->name.'.pdf' }}" target="_blank" rel="noopener" class="dl-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                                    PDFをダウンロード
                                </a>
                            </div>
                        @endif

                        <div class="box">
                            <details class="mini-acc">
                                <summary class="box-title">📝 案件概要<span class="mini-chev"></span></summary>
                                <p>{{ $project->overviewText() }}</p>
                            </details>
                        </div>

                        <div class="box">
                            <p class="box-title">📨 案内フォーム</p>
                            <div class="muted">この案件専用の招待リンクです。お客様が送信するとLINEでご案内が届きます。</div>

                            <input type="text" readonly value="{{ $inviteData[$project->id]['url'] }}">
                            <div class="copy-row">
                                <button type="button" class="copy copy-link"
                                        onclick="copyToClipboard({{ Illuminate\Support\Js::from($inviteData[$project->id]['url']) }})">
                                    リンクをコピー
                                </button>
                                <a href="{{ $inviteData[$project->id]['url'] }}" target="_blank" rel="noopener" class="copy">
                                    フォームを確認
                                </a>
                            </div>
                        </div>
                    </div>
                </details>
            @endforeach
        @empty
            <p class="text-gray-400 text-center py-10">現在紹介可能な案件はありません。</p>
        @endforelse
    </div>
</div>
@endsection
