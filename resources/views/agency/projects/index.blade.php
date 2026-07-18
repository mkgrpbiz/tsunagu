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
.mk-cases button.copy{border:none;border-radius:12px;padding:12px 14px;font-weight:800;cursor:pointer;background:#111827;color:#fff;width:100%;margin-top:8px}
.mk-cases button.copy.copy-link{background:#2563eb}
.mk-cases pre{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:12px;font-size:13px;line-height:1.75;white-space:pre-wrap;word-break:break-word;margin:10px 0 0}
.mk-cases .mk-cat{background:transparent;border:none;border-left:4px solid #ea580c;padding:8px 12px;margin:28px 0 10px}
.mk-cases .mk-cat:first-of-type{margin-top:8px}
.mk-cases .mk-cat-title{color:#9a3412;font-size:15px;font-weight:800;margin-bottom:4px}
.mk-cases .mkp-photo{width:100%;aspect-ratio:1/1;overflow:hidden;border-radius:16px;border:1px solid #e5e7eb;background:#fff;margin-top:10px}
.mk-cases .mkp-photo img{width:100%;height:100%;object-fit:contain;display:block}
</style>
@endpush

@section('content')
<div class="mk-cases" id="mkCases">
    <div class="mk-wrap">
        <div class="box" style="margin-bottom:20px">
            <p class="box-title">✅ おしごとナビ（全案件まとめ紹介リンク）</p>
            <div class="muted">掲載中の全案件を1ページにまとめたページです。個別の招待リンクの代わりにこちらをシェアできます。</div>
            <input type="text" readonly value="{{ $oshigotoUrl }}">
            <button type="button" class="copy copy-link" onclick="copyToClipboard({{ Illuminate\Support\Js::from($oshigotoUrl) }})">
                リンクをコピー
            </button>
        </div>

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
                        @if ($project->image_path)
                            <div class="box">
                                <p class="box-title">✅ 集客画像</p>
                                <div class="mkp-photo">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($project->image_path) }}" alt="{{ $project->name }} 集客画像" loading="lazy">
                                </div>
                            </div>
                        @endif

                        <div class="box">
                            <p class="box-title">✅ 紹介報酬</p>
                            <p>{{ $project->description }}</p>
                        </div>

                        <div class="box">
                            <p class="box-title">✅ 着金タイミング</p>
                            <p>{{ $project->payment_timing }}</p>
                        </div>

                        <div class="box">
                            <p class="box-title">✅ 招待リンク・募集文</p>
                            <div class="muted">この案件専用の招待リンクです。募集文にはリンクが自動で組み込まれています。</div>

                            <input type="text" readonly value="{{ $inviteData[$project->id]['url'] }}">
                            <button type="button" class="copy copy-link"
                                    onclick="copyToClipboard({{ Illuminate\Support\Js::from($inviteData[$project->id]['url']) }})">
                                リンクのみコピー
                            </button>

                            <pre>{{ $inviteData[$project->id]['template'] }}</pre>
                            <button type="button" class="copy"
                                    onclick="copyToClipboard({{ Illuminate\Support\Js::from($inviteData[$project->id]['template']) }})">
                                募集文をコピー
                            </button>
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
