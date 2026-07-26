@php
    $ogImagePath = \App\Models\HomePageContent::current()->og_image_path;
    $resolvedOgTitle = $ogTitle ?? trim($__env->yieldContent('title', 'TSUNAGU Partner Network'));
@endphp
<meta property="og:type" content="website">
<meta property="og:site_name" content="TSUNAGU Partner Network">
<meta property="og:title" content="{{ $resolvedOgTitle }}">
@if ($ogImagePath)
    <meta property="og:image" content="{{ \Illuminate\Support\Facades\Storage::url($ogImagePath) }}">
@endif
<meta name="twitter:card" content="summary_large_image">
