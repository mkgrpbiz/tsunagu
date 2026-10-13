@extends('layouts.agency')

@section('title', $category->name)

@push('styles')
<style>
.mk-cases{margin:0;background:transparent;color:#111827;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans",Meiryo,sans-serif}
.mk-cases *{box-sizing:border-box}
.mk-cases .mk-wrap{max-width:760px;margin:0 auto;padding:0}
.mk-cases .back-link{display:inline-flex;align-items:center;gap:4px;font-size:13px;color:#6b7280;text-decoration:none;margin-bottom:10px}
.mk-cases .page-title{font-weight:900;font-size:18px;color:#0f172a;margin-bottom:12px}
.mk-cases a.cat-card{display:flex;align-items:center;gap:12px;padding:14px;background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 24px rgba(0,0,0,.06);margin:14px 0;text-decoration:none;color:inherit}
.mk-cases a.cat-card:hover{border-color:#bfdbfe;background:#f8fafc}
.mk-cases .cat-body{flex:1;min-width:0;display:flex;align-items:center;gap:8px}
.mk-cases .cat-title{font-weight:900;font-size:16px;line-height:1.35;color:#0f172a}
.mk-cases .cat-count{flex-shrink:0;padding:2px 10px;border-radius:999px;background:#dbeafe;color:#2563eb;font-size:11.5px;font-weight:800}
.mk-cases .arrow{width:8px;height:8px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(-45deg);flex-shrink:0}
.mk-cases .filter-form{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:14px;margin-bottom:16px;display:flex;gap:10px}
.mk-cases .filter-form input[type=text]{flex:1;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:13px;background:#fff}
.mk-cases .filter-form button{flex-shrink:0;background:#2563eb;color:#fff;border:none;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer}
@media (max-width:480px){.mk-cases .filter-form{flex-direction:column}}
</style>
@endpush

@section('content')
<div class="mk-cases">
    <div class="mk-wrap">
        <a href="{{ route('agency.projects.index') }}" class="back-link">&lt; 案件一覧へ戻る</a>
        <div class="page-title">{{ $category->name }}</div>

        <form method="GET" action="{{ route('agency.projects.category', $category) }}" class="filter-form">
            <input type="text" name="keyword" placeholder="キーワード検索（全業種から勤務地・職種・雇用形態などで検索）">
            <button type="submit">検索</button>
        </form>

        @forelse ($industries as $industry)
            <a href="{{ route('agency.projects.industry', [$category, $industry]) }}" class="cat-card">
                <div class="cat-body">
                    <div class="cat-title">{{ $industry->name }}</div>
                    <div class="cat-count">{{ $industry->projects_count }}件</div>
                </div>
                <span class="arrow"></span>
            </a>
        @empty
            <p class="text-gray-400 text-center py-10">現在紹介可能な案件はありません。</p>
        @endforelse
    </div>
</div>
@endsection
