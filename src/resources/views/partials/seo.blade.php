@php
$social = empty($seo['image']) ? \App\Support\SocialImage::forUrl($seo['canonical'] ?? null) : null;
$ogImage = $seo['image'] ?? ($social['url'] ?? asset('assets/images/hero-img7.png'));
@endphp
@unless (in_array(request()->getHost(), ['localhost', '127.0.0.1', '[::1]'], true))
    @include('partials.analytics')
    @include('partials.adsense')
@endunless
@if (config('app.google_site_verification'))
    <meta name="google-site-verification" content="{{ config('app.google_site_verification') }}">
@endif
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow,max-image-preview:large,max-snippet:-1' }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    @if ($social)
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/jpeg">
@endif
    <meta property="og:image:alt" content="{{ $seo['title'] }}">
    @if (config('services.social.facebook_app_id'))
    <meta property="fb:app_id" content="{{ config('services.social.facebook_app_id') }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:domain" content="{{ parse_url(config('app.url'), PHP_URL_HOST) }}">
    <meta name="twitter:url" content="{{ $seo['canonical'] }}">
    @if (config('services.social.twitter'))
    <meta name="twitter:site" content="{{ config('services.social.twitter') }}">
    <meta name="twitter:creator" content="{{ config('services.social.twitter') }}">
    @endif
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $seo['title'] }}">
    <meta name="pinterest-rich-pin" content="true">
    <meta name="theme-color" content="#ffffff">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta itemprop="name" content="{{ $seo['title'] }}">
    <meta itemprop="description" content="{{ $seo['description'] }}">
    <meta itemprop="image" content="{{ $ogImage }}">
    <link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="LLMs.txt">
    <script type="application/ld+json">{!! \App\Support\SiteSchema::json() !!}</script>
    @foreach ($seo['schema'] ?? [] as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
