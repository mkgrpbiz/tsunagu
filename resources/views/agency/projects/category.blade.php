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
.mk-cases .proj-acc-body p{margin:10px 0 0;font-size:13px;line-height:1.75;white-space:pre-line;color:#374151}
.mk-cases .proj-acc-body a.detail-link{display:inline-flex;align-items:center;gap:4px;margin-top:10px;color:#2563eb;font-weight:700;font-size:13px;text-decoration:none}
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
            <details class="proj-acc">
                <summary>
                    <div class="thumb">
                        @if ($project->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($project->image_path) }}" alt="">
                        @else
                            <img src="{{ asset('tsunagu-logo.png') }}" alt="">
                        @endif
                    </div>
                    <div class="proj-body">
                        <div class="proj-title">{{ $project->name }}</div>
                        <div class="proj-price">{{ $project->description }}</div>
                    </div>
                    <span class="chev"></span>
                </summary>
                <div class="proj-acc-body">
                    <p>{{ $category->has_job_fields && $project->job_description ? $project->job_description : $project->description }}</p>
                    <a href="{{ route('agency.projects.show', $project) }}" class="detail-link">案件詳細・招待リンクを見る →</a>
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
