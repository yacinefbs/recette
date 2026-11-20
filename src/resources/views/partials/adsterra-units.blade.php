@unless (in_array(request()->getHost(), ['localhost', '127.0.0.1', '[::1]'], true))
    <section class="tt-adsterra" data-adsterra-placement aria-label="Sponsored advertisements">
        <div class="tt-adsterra__content">
            <div class="tt-adsterra__unit tt-adsterra__native" data-adsterra-native>
                <div id="{{ config('services.adsterra.native_banner.container_id') }}"></div>
            </div>
            <div class="tt-adsterra__unit tt-adsterra__responsive-banner" data-adsterra-responsive-banner></div>
            <div class="tt-adsterra__unit tt-adsterra__rectangle" data-adsterra-rectangle></div>
            <a class="tt-adsterra__smartlink" href="{{ config('services.adsterra.smartlink') }}" target="_blank" rel="sponsored nofollow noopener" data-adsterra-smartlink>
                Sponsored link
            </a>
        </div>
        <aside class="tt-adsterra__rail" data-adsterra-rail aria-label="Advertisement"></aside>
    </section>
@endunless
