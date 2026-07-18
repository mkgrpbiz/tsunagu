@extends('layouts.public')

@section('title', 'おしごとナビ｜案件一覧')

@push('styles')
<style>
.og-wrap{max-width:760px;margin:0 auto;padding:0 1.25rem 4rem;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans",Meiryo,sans-serif;color:#111827}
.og-wrap *{box-sizing:border-box}
.og-header{text-align:center;margin-bottom:16px}
.og-logo{display:block;max-width:100%;width:100%;height:auto;margin:0 auto;border-radius:16px}
.og-title{font-weight:900;font-size:20px;color:#0f172a}
.og-sub{margin-top:6px;font-size:12.5px;color:#6b7280}
.og-notice{margin-top:16px;padding:12px 16px;border-radius:14px;font-size:13px;line-height:1.7}
.og-notice.ng{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.og-cat{border-left:4px solid #16a34a;padding:8px 12px;margin:28px 0 10px}
.og-cat:first-of-type{margin-top:20px}
.og-cat-title{color:#166534;font-size:15px;font-weight:800;margin-bottom:4px}
.og-case{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 24px rgba(0,0,0,.06);overflow:hidden;margin:10px 0}
.og-case summary{list-style:none;cursor:pointer;padding:14px;font-weight:800;display:flex;align-items:center;justify-content:space-between;gap:10px;background:linear-gradient(180deg,#f0fdf4,#fff);border-bottom:1px solid #e5e7eb}
.og-case summary::-webkit-details-marker{display:none}
.og-chev{width:10px;height:10px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);transition:transform .18s ease;flex-shrink:0}
.og-case[open] .og-chev{transform:rotate(-135deg)}
.og-body{padding:12px}
.og-photo{width:100%;aspect-ratio:1/1;overflow:hidden;border-radius:16px;border:1px solid #e5e7eb;background:#fff;margin-bottom:12px}
.og-photo img{width:100%;height:100%;object-fit:contain;display:block}
.og-offer{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:12px;font-size:13.5px;line-height:1.75;white-space:pre-line;word-break:break-word}
.og-apply{display:block;width:100%;margin-top:12px;text-align:center;text-decoration:none;background:#16a34a;color:#fff;font-weight:800;border-radius:14px;padding:13px 16px;box-shadow:0 10px 22px rgba(22,163,74,.20)}
.og-apply.disabled{background:#e5e7eb;color:#9ca3af;pointer-events:none}
.og-footer{text-align:center;margin:40px 0 10px;font-size:11px;letter-spacing:.05em;color:#a1a1aa}
</style>
@endpush

@section('content')
<div class="og-wrap">
    <div class="og-header">
        <img src="{{ \Illuminate\Support\Facades\Storage::url('oshigoto/logo.png') }}" alt="おしごとナビ" class="og-logo">
        <div class="og-title">募集中のおしごと</div>
        <div class="og-sub">気になる案件名をタップして詳細をご確認ください。</div>
    </div>

    @unless ($agency)
        <div class="og-notice ng">このページは紹介者専用リンクからご覧ください。「お申し込みはこちら」ボタンは無効になっています。</div>
    @endunless

    @forelse ($projectsByCategory as $categoryName => $projects)
        <div class="og-cat">
            <div class="og-cat-title">{{ $categoryName }}</div>
        </div>

        @foreach ($projects as $project)
            <details class="og-case">
                <summary>{{ $project->name }}<span class="og-chev"></span></summary>
                <div class="og-body">
                    @if ($project->image_path)
                        <div class="og-photo">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($project->image_path) }}" alt="{{ $project->name }}" loading="lazy">
                        </div>
                    @endif

                    <div class="og-offer">{{ $offerTexts[$project->id] }}</div>

                    @if ($agency)
                        <a href="{{ $applyUrls[$project->id] }}" class="og-apply">お申し込みはこちら</a>
                    @else
                        <span class="og-apply disabled">お申し込みはこちら（紹介リンクが必要です）</span>
                    @endif
                </div>
            </details>
        @endforeach
    @empty
        <p class="text-center text-gray-400 py-10">現在掲載中の案件はありません。</p>
    @endforelse

    <div class="og-footer">© {{ now()->year }} TSUNAGU. All rights reserved.</div>
</div>
@endsection
