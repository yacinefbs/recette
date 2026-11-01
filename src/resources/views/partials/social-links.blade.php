@php
    $networks = [
        'facebook' => ['fab fa-facebook-f', 'Facebook'],
        'instagram' => ['fab fa-instagram', 'Instagram'],
        'x' => ['fab fa-twitter', 'X'],
        'pinterest' => ['fab fa-pinterest-p', 'Pinterest'],
        'youtube' => ['fab fa-youtube', 'YouTube'],
        'tiktok' => ['fab fa-tiktok', 'TikTok'],
    ];
    $links = array_filter(config('services.social.urls', []));
@endphp
@if ($links)
    <div class="{{ $wrapClass ?? '' }}">
        <ul class="list-unstyled d-flex flex-wrap" style="gap:14px">
            @foreach ($networks as $key => [$icon, $label])
                @if (!empty($links[$key]))
                    <li><a href="{{ $links[$key] }}" target="_blank" rel="noopener me" aria-label="{{ $label }}"><i class="{{ $icon }}"></i></a></li>
                @endif
            @endforeach
        </ul>
    </div>
@endif
