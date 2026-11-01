@php
    $shareUrl = $url ?? url()->current();
    $shareTitle = $title ?? config('app.name');
    $shareImage = $image ?? null;
    $shareId = 'share-' . substr(md5($shareUrl), 0, 8);
    $tag = fn (string $network) => $shareUrl . (str_contains($shareUrl, '?') ? '&' : '?') . 'utm_source=' . $network . '&utm_medium=social&utm_campaign=share';
    $enc = fn (string $v) => rawurlencode($v);
    $links = [
        'whatsapp' => 'https://wa.me/?text=' . $enc($shareTitle . ' ' . $tag('whatsapp')),
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $enc($tag('facebook')),
        'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $enc($tag('pinterest')) . ($shareImage ? '&media=' . $enc($shareImage) : '') . '&description=' . $enc($shareTitle),
    ];
@endphp
<style>
    .tt-share { background: #fff; border: 1px solid #f0e6da; border-radius: 18px; padding: 1.1rem 1.25rem; margin: 1.5rem 0; box-shadow: 0 10px 30px -22px rgba(27,42,65,.5); }
    .tt-share-head { display: flex; align-items: center; gap: .9rem; padding-bottom: .9rem; margin-bottom: .9rem; border-bottom: 1px solid #f3ece2; }
    .tt-share-head img { width: 64px; height: 64px; object-fit: cover; border-radius: 12px; flex: none; }
    .tt-share-head strong { display: block; color: #1b2a41; line-height: 1.25; }
    .tt-share-head small { color: #6b7280; }
    .tt-share-title { font-weight: 700; color: #1b2a41; margin: 0 0 .65rem; font-size: 1rem; }
    .tt-share-btns { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; }
    .tt-share-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .3rem; min-height: 64px; padding: .5rem; border: 0; border-radius: 14px; color: #fff !important; font: 600 .85rem/1.1 inherit; text-decoration: none !important; cursor: pointer; transition: transform .15s, filter .15s, box-shadow .15s; }
    .tt-share-btn:hover { transform: translateY(-2px); filter: brightness(1.06); box-shadow: 0 10px 18px -10px rgba(0,0,0,.5); color: #fff; }
    .tt-share-btn:focus-visible { outline: 3px solid rgba(255,198,0,.9); outline-offset: 2px; }
    .tt-share-btn svg { width: 22px; height: 22px; fill: currentColor; }
    .tt-share-btn.wa { background: #25d366; }
    .tt-share-btn.fb { background: #1877f2; }
    .tt-share-btn.pin { background: #e60023; }
    .tt-share-btn.copy { background: #f4f1ec; color: #1b2a41 !important; border: 1px solid #e7ddd0; }
    .tt-share-btn.copy.copied { background: #e6f6ec; color: #1b7a43 !important; border-color: #b7e4c9; }
    @media (max-width: 480px) { .tt-share-btns { grid-template-columns: repeat(2, 1fr); } }
    @media print { .tt-share { display: none !important; } }
</style>
<div class="tt-share" id="{{ $shareId }}" data-url="{{ $tag('copy') }}" data-title="{{ $shareTitle }}">
    @if (!empty($meta) || $shareImage)
        <div class="tt-share-head">
            @if ($shareImage)<img src="{{ $shareImage }}" alt="{{ $shareTitle }}" loading="lazy" width="64" height="64">@endif
            <div>
                <strong>{{ $shareTitle }}</strong>
                @if (!empty($meta))<small>{{ $meta }}</small>@endif
            </div>
        </div>
    @endif
    <p class="tt-share-title">{{ $label ?? 'Share this recipe' }}</p>
    <div class="tt-share-btns">
        <a class="tt-share-btn wa" href="{{ $links['whatsapp'] }}" target="_blank" rel="noopener noreferrer" data-net="whatsapp">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.4 15.1L2 22l5-1.6A9.9 9.9 0 1 0 12.04 2zm0 1.8a8.1 8.1 0 1 1-4.3 15l-.3-.2-3 .9.9-2.9-.2-.3a8.1 8.1 0 0 1 6.9-12.5zm-3 3.6c-.2 0-.5.1-.7.3-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.1 4.9 4.3 2.4 1 2.9.8 3.4.7.5-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.4l-1.8-.9c-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.6.1-.3-.1-1.1-.4-2.1-1.3-.8-.7-1.3-1.5-1.4-1.8-.2-.3 0-.4.1-.5l.4-.5.3-.4c.1-.2 0-.3 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4z"/></svg>
            WhatsApp
        </a>
        <a class="tt-share-btn fb" href="{{ $links['facebook'] }}" target="_blank" rel="noopener noreferrer" data-net="facebook">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 22v-8.2h2.8l.4-3.3h-3.2V8.4c0-.9.3-1.6 1.6-1.6h1.7V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.4H7.3v3.3h2.8V22h3.4z"/></svg>
            Facebook
        </a>
        <a class="tt-share-btn pin" href="{{ $links['pinterest'] }}" target="_blank" rel="noopener noreferrer" data-net="pinterest">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-3.6 19.3c-.1-.8-.2-2 0-2.9l1.2-5s-.3-.6-.3-1.5c0-1.4.8-2.5 1.8-2.5.9 0 1.3.7 1.3 1.4 0 .9-.5 2.1-.8 3.3-.2 1 .5 1.8 1.5 1.8 1.8 0 3.2-1.9 3.2-4.7 0-2.4-1.8-4.2-4.3-4.2-2.9 0-4.6 2.200-4.6 4.500 0 .9.3 1.800.8 2.300.1.1.1.2.1.3l-.3 1.200c0 .2-.2.2-.4.1-1.300-.6-2.100-2.500-2.100-4 0-3.300 2.400-6.300 6.900-6.300 3.600 0 6.400 2.600 6.400 6 0 3.600-2.300 6.500-5.400 6.500-1.100 0-2.100-.6-2.400-1.200l-.7 2.500c-.2.900-.9 2.100-1.300 2.800A10 10 0 1 0 12 2z"/></svg>
            Pinterest
        </a>
        <button type="button" class="tt-share-btn copy" data-copy>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 13.4a1 1 0 0 1 0-1.4l3-3a3.2 3.2 0 0 1 4.5 4.500l-1.800 1.800a1 1 0 1 1-1.400-1.400l1.800-1.800a1.200 1.200 0 0 0-1.700-1.700l-3 3a1 1 0 0 1-1.400 0zm2.800-2.800a1 1 0 0 1 0 1.400l-3 3a3.200 3.200 0 0 1-4.500-4.500l1.800-1.800a1 1 0 1 1 1.400 1.400l-1.800 1.800a1.200 1.200 0 0 0 1.700 1.700l3-3a1 1 0 0 1 1.400 0z"/></svg>
            <span data-copy-label>Copy link</span>
        </button>
    </div>
</div>
<script>
    (function () {
        var box = document.getElementById(@json($shareId));
        if (!box) { return; }
        var btn = box.querySelector('[data-copy]');
        var label = box.querySelector('[data-copy-label]');
        btn.addEventListener('click', function () {
            var url = box.getAttribute('data-url');
            var done = function () {
                btn.classList.add('copied'); label.textContent = 'Link copied!';
                setTimeout(function () { btn.classList.remove('copied'); label.textContent = 'Copy link'; }, 2200);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done, function () { window.prompt('Copy this link:', url); });
            } else {
                var t = document.createElement('textarea'); t.value = url; t.style.position = 'fixed'; t.style.opacity = '0';
                document.body.appendChild(t); t.select();
                try { document.execCommand('copy'); done(); } catch (e) { window.prompt('Copy this link:', url); }
                document.body.removeChild(t);
            }
        });
    })();
</script>
