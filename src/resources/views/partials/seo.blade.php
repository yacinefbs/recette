@include('partials.analytics')
@include('partials.adsense')
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
    <meta property="og:image" content="{{ $seo['image'] ?? asset('assets/images/hero-img7.png') }}">
    <meta property="og:image:secure_url" content="{{ $seo['image'] ?? asset('assets/images/hero-img7.png') }}">
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
    <meta name="twitter:image" content="{{ $seo['image'] ?? asset('assets/images/hero-img7.png') }}">
    <meta name="twitter:image:alt" content="{{ $seo['title'] }}">
    <meta name="pinterest-rich-pin" content="true">
    <meta name="theme-color" content="#ffffff">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta itemprop="name" content="{{ $seo['title'] }}">
    <meta itemprop="description" content="{{ $seo['description'] }}">
    <meta itemprop="image" content="{{ $seo['image'] ?? asset('assets/images/hero-img7.png') }}">
    <link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="LLMs.txt">
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => config('app.name'),
                'url' => url('/'),
                'logo' => asset('assets/images/logo.svg'),
                'email' => config('app.contact_email'),
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'url' => url('/'),
                'name' => config('app.name'),
                'inLanguage' => 'en',
                'publisher' => ['@id' => url('/').'#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => route('recipes.index').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @foreach ($seo['schema'] ?? [] as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
