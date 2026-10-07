@extends('layouts.agency')

@section('title', $category->name)

@push('styles')
<style>
.mk-cases{margin:0;background:transparent;color:#111827;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans",Meiryo,sans-serif}
.mk-cases *{box-sizing:border-box}
.mk-cases .mk-wrap{max-width:760px;margin:0 auto;padding:0}
.mk-cases .back-link{display:inline-flex;align-items:center;gap:4px;font-size:13px;color:#6b7280;text-decoration:none;margin-bottom:10px}
.mk-cases .page-title{font-weight:900;font-size:18px;color:#0f172a;margin-bottom:12px}
.mk-cases .filter-form{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:14px;margin-bottom:16px;display:flex;gap:10px}
.mk-cases .filter-form input[type=text]{flex:1;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:13px;background:#fff}
.mk-cases .filter-form .filter-actions{display:flex;gap:8px}
.mk-cases .filter-form button{flex:1;background:#2563eb;color:#fff;border:none;border-radius:8px;padding:9px;font-size:13px;font-weight:700;cursor:pointer}
.mk-cases .filter-form a.reset{flex-shrink:0;display:flex;align-items:center;justify-content:center;padding:9px 14px;border-radius:8px;border:1px solid #d1d5db;color:#6b7280;font-size:13px;text-decoration:none}
.mk-cases details.proj-acc{border:1px solid #e5e7eb;border-radius:14px;background:#fff;margin:10px 0;overflow:hidden}
.mk-cases details.proj-acc summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:12px;padding:12px}
.mk-cases details.proj-acc summary::-webkit-details-marker{display:none}
.mk-cases details.proj-acc:hover summary{background:#f8fafc}
.mk-cases .thumb{width:56px;height:56px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#eff6ff}
.mk-cases .thumb img{width:100%;height:100%;object-fit:cover;display:block}
.mk-cases .proj-body{flex:1;min-width:0}
.mk-cases .proj-title{font-weight:800;font-size:14.5px;line-height:1.4;color:#0f172a}
.mk-cases .proj-price{margin-top:4px;font-size:12.5px;line-height:1.5;color:#2563eb;font-weight:700;white-space:pre-line;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.mk-cases .chev{width:9px;height:9px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);transition:transform .18s ease;flex-shrink:0}
.mk-cases details.proj-acc[open] summary .chev{transform:rotate(-135deg)}
.mk-cases .proj-acc-body{padding:0 14px 14px;border-top:1px solid #f1f5f9}
.mk-cases .box{border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:12px;margin:12px 0}
.mk-cases .box-title{font-weight:800;margin:0 0 8px;display:flex;align-items:center;gap:8px;font-size:13px}
.mk-cases .box p{margin:0;font-size:13px;line-height:1.75;white-space:pre-line;color:#374151}
.mk-cases .muted{margin-top:8px;color:#6b7280;font-size:12px;line-height:1.6}
.mk-cases input[readonly]{border:1px solid #e5e7eb;border-radius:12px;padding:10px;font-size:13px;background:#f9fafb;color:#374151;width:100%}
.mk-cases button.copy,.mk-cases a.copy{border:none;border-radius:12px;padding:11px 14px;font-weight:800;cursor:pointer;background:#111827;color:#fff;width:100%;margin-top:8px;display:block;text-align:center;text-decoration:none;box-sizing:border-box;font-size:13px}
.mk-cases button.copy.copy-link{background:#2563eb}
.mk-cases .copy-row{display:flex;gap:8px}
.mk-cases .copy-row .copy{width:50%;margin-top:8px}
.mk-cases .dl-btn{display:inline-flex;align-items:center;gap:6px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:999px;padding:8px 16px;font-weight:700;font-size:13px;text-decoration:none}
.mk-cases .dl-btn:hover{background:#dbeafe}
.mk-cases .dl-btn svg{width:16px;height:16px;flex-shrink:0}
.mk-cases .mini-acc summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:8px;margin:0}
.mk-cases .mini-acc summary::-webkit-details-marker{display:none}
.mk-cases .mini-acc .mini-copy-btn{display:none;align-items:center;justify-content:center;gap:4px;padding:4px 10px;border:none;border-radius:999px;background:#eff6ff;color:#2563eb;font-size:11.5px;font-weight:700;cursor:pointer;flex-shrink:0;white-space:nowrap}
.mk-cases .mini-acc[open] .mini-copy-btn{display:inline-flex}
.mk-cases .mini-acc .mini-chev{width:8px;height:8px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);transition:transform .18s ease;margin-left:auto;flex-shrink:0}
.mk-cases .mini-acc[open] .mini-chev{transform:rotate(-135deg)}
.mk-cases .mini-acc p{margin-top:8px}
.mk-cases .job-spec{margin:10px 0 0;padding:0}
.mk-cases .job-spec div{padding:8px 0;border-bottom:1px solid #f1f5f9}
.mk-cases .job-spec div:last-child{border-bottom:none}
.mk-cases .job-spec dt{font-size:11.5px;font-weight:800;color:#6b7280;margin-bottom:2px}
.mk-cases .job-spec dd{font-size:13px;line-height:1.7;white-space:pre-line;margin:0;color:#374151}
.mk-cases .pagination-wrap{margin-top:16px}
@media (max-width:480px){.mk-cases .filter-form{flex-direction:column}}
</style>
@endpush

@section('content')
<div class="mk-cases">
    <div class="mk-wrap">
        <a href="{{ route('agency.projects.index') }}" class="back-link">&lt; 案件一覧へ戻る</a>
        <div class="page-title">{{ $category->name }}（{{ $projects->total() }}件）</div>

        @if ($category->has_job_fields)
            <form method="GET" action="{{ route('agency.projects.category', $category) }}" class="filter-form">
                <input type="text" name="keyword" value="{{ $keyword }}" placeholder="キーワード検索（勤務地・職種・雇用形態など募集要項から検索）">
                <div class="filter-actions">
                    <button type="submit">検索</button>
                    @if ($keyword !== '')
                        <a href="{{ route('agency.projects.category', $category) }}" class="reset">リセット</a>
                    @endif
                </div>
            </form>
        @endif

        @forelse ($projects as $project)
            @php
                $jobSpecItems = $category->has_job_fields ? array_filter([
                    '💼 仕事内容' => $project->job_description,
                    '📍 勤務地' => $project->work_location,
                    '💴 年収' => $project->annual_income,
                    '🧾 給与・待遇' => $project->salary_benefits,
                    '🏖️ 休日・休暇' => $project->holidays,
                    '✅ 応募資格' => $project->qualifications,
                    '👤 募集年齢' => $project->age_requirement,
                    '📋 雇用形態' => $project->employment_type,
                    '⏰ 勤務時間' => $project->working_hours,
                ], fn ($value) => filled($value)) : [];
            @endphp
            <details class="proj-acc">
                <summary>
                    @unless ($category->has_job_fields)
                        <div class="thumb">
                            @if ($project->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($project->image_path) }}" alt="">
                            @else
                                <img src="{{ asset('tsunagu-logo.png') }}" alt="">
                            @endif
                        </div>
                    @endunless
                    <div class="proj-body">
                        <div class="proj-title">{{ $project->name }}</div>
                        <div class="proj-price">{{ $project->description }}</div>
                    </div>
                    <span class="chev"></span>
                </summary>
                <div class="proj-acc-body">
                    @if (count($jobSpecItems) > 0)
                        <div class="box">
                            <details class="mini-acc">
                                <summary class="box-title">
                                    📋 募集要項
                                    <span class="mini-chev"></span>
                                </summary>
                                <div class="job-spec">
                                    @foreach ($jobSpecItems as $label => $value)
                                        <div>
                                            <dt>{{ $label }}</dt>
                                            <dd>{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    @endif

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
                                        onclick="event.stopPropagation(); copyToClipboard({{ Illuminate\Support\Js::from($project->overviewText()) }})">📋 概要コピー</button>
                                <span class="mini-chev"></span>
                            </summary>
                            <p>{{ $project->overviewText() }}</p>
                        </details>
                    </div>

                    <div class="box">
                        <p class="box-title">📨 案内フォーム</p>
                        <div class="muted">この案件専用の招待リンクです。お客様が送信するとLINEでご案内が届きます。</div>

                        <input type="text" readonly value="{{ $inviteUrls[$project->id] }}">
                        <div class="copy-row">
                            <button type="button" class="copy copy-link"
                                    onclick="copyToClipboard({{ Illuminate\Support\Js::from($inviteUrls[$project->id]) }})">
                                リンクをコピー
                            </button>
                            <a href="{{ $inviteUrls[$project->id] }}" target="_blank" rel="noopener" class="copy">
                                フォームを確認
                            </a>
                        </div>
                    </div>
                </div>
            </details>
        @empty
            <p class="text-gray-400 text-center py-10">該当する案件がありません。</p>
        @endforelse

        <div class="pagination-wrap">
            {{ $projects->links() }}
        </div>
    </div>
</div>
@endsection
