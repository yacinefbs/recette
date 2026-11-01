@php($s = $item['model'])
<article class="tt-sub-card {{ !empty($item['best']) ? 'is-best' : '' }}">
    <header class="tt-sub-card__head">
        <span class="tt-sub-card__icon" aria-hidden="true"><i class="fas {{ $s->icon ?: 'fa-utensils' }}"></i></span>
        <div>
            <h3>{{ $s->substitute_name }}</h3>
            <p class="tt-sub-card__uses">
                @foreach ($s->purposes as $p)<span class="tt-tag tt-tag--use">{{ ucfirst($p) }}</span>@endforeach
            </p>
        </div>
        @if (!empty($item['best']))<span class="tt-badge">Best match</span>@endif
    </header>

    <p class="tt-sub-card__ratio"><strong>Ratio:</strong> {{ $s->ratio_description }}</p>

    @if (!empty($item['scaled']))
        <div class="tt-sub-card__calc" aria-live="polite">
            @if ($item['scaled']['lines'])
                <strong>For your quantity, use:</strong> {{ implode(' + ', $item['scaled']['lines']) }}
            @endif
            @if ($item['scaled']['note'])<small>{{ $item['scaled']['note'] }}</small>@endif
        </div>
    @endif

    <dl class="tt-sub-card__details">
        @if ($s->advantages)<div><dt>Advantages</dt><dd>{{ $s->advantages }}</dd></div>@endif
        @if ($s->limitations)<div><dt>Limitations</dt><dd>{{ $s->limitations }}</dd></div>@endif
        @if ($s->impact)<div><dt>Taste &amp; texture</dt><dd>{{ $s->impact }}</dd></div>@endif
        @if ($s->tip)<div><dt>Tip</dt><dd>{{ $s->tip }}</dd></div>@endif
    </dl>

    <p class="tt-sub-card__tags">
        @foreach ($s->dietary_tags as $t)<span class="tt-tag">{{ ucfirst($t) }}</span>@endforeach
        @foreach ($s->allergens as $a)
            <span class="tt-tag tt-tag--warn">{{ $a === 'check the label' ? 'Check the label for allergens' : 'Contains: '.$a }}</span>
        @endforeach
    </p>
</article>
