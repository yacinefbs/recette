@php($slotId = config('services.adsense.slots.'.$slot))
@if ($slotId && !in_array(request()->getHost(), ['localhost', '127.0.0.1', '[::1]'], true))
    <aside class="tt-ad" aria-label="Advertisement">
        <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-3690879797577737" data-ad-slot="{{ $slotId }}" data-ad-format="auto" data-full-width-responsive="true"></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    </aside>
@endif
