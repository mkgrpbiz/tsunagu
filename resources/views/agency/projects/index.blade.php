@extends('layouts.agency')

@section('title', '案件一覧')

@push('styles')
<style>
.mk-cases{margin:0;background:transparent;color:#111827;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans",Meiryo,sans-serif}
.mk-cases *{box-sizing:border-box}
.mk-cases .mk-wrap{max-width:760px;margin:0 auto;padding:0}
.mk-cases details.cat-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 24px rgba(0,0,0,.06);overflow:hidden;margin:14px 0}
.mk-cases details.cat-card summary{list-style:none;cursor:pointer;padding:14px;display:flex;align-items:center;gap:12px;background:linear-gradient(180deg,#eff6ff,#fff);border-bottom:1px solid #e5e7eb}
.mk-cases details.cat-card summary::-webkit-details-marker{display:none}
.mk-cases .thumb{width:56px;height:56px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#eff6ff}
.mk-cases .thumb img{width:100%;height:100%;object-fit:cover;display:block}
.mk-cases .cat-body{flex:1;min-width:0;display:flex;align-items:center;gap:8px}
.mk-cases .cat-title{font-weight:900;font-size:16px;line-height:1.35;color:#0f172a}
.mk-cases .cat-count{flex-shrink:0;padding:2px 10px;border-radius:999px;background:#dbeafe;color:#2563eb;font-size:11.5px;font-weight:800}
.mk-cases .chev{width:10px;height:10px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);transition:transform .18s ease;flex-shrink:0}
.mk-cases details.cat-card[open] > summary .chev{transform:rotate(-135deg)}
.mk-cases .cat-projects{padding:12px}
.mk-cases a.proj-card{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;margin:10px 0;text-decoration:none;color:inherit}
.mk-cases a.proj-card:hover{border-color:#bfdbfe;background:#f8fafc}
.mk-cases .proj-body{flex:1;min-width:0}
.mk-cases .proj-title{font-weight:800;font-size:14.5px;line-height:1.4;color:#0f172a}
.mk-cases .proj-price{margin-top:4px;font-size:12.5px;line-height:1.5;color:#2563eb;font-weight:700;white-space:pre-line;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.mk-cases .arrow{width:8px;height:8px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(-45deg);flex-shrink:0}
</style>
@endpush

@section('content')
<div class="mk-cases" id="mkCases">
    <div class="mk-wrap">
        @forelse ($categories as $row)
            <details class="cat-card" open>
                <summary>
                    <div class="thumb">
                        <img src="{{ asset('tsunagu-logo.png') }}" alt="">
                    </div>
                    <div class="cat-body">
                        <div class="cat-title">{{ $row['category']->name }}</div>
                        <div class="cat-count">{{ $row['projects']->count() }}件</div>
                    </div>
                    <span class="chev"></span>
                </summary>
                <div class="cat-projects">
                    @foreach ($row['projects'] as $project)
                        <a href="{{ route('agency.projects.show', $project) }}" class="proj-card">
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
                            <span class="arrow"></span>
                        </a>
                    @endforeach
                </div>
            </details>
        @empty
            <p class="text-gray-400 text-center py-10">現在紹介可能な案件はありません。</p>
        @endforelse
    </div>
</div>
@endsection
