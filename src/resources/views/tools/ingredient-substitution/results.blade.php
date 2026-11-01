<section class="tt-sub-results" aria-live="polite" aria-labelledby="sub-results-title">
    @if ($result['status'] === 'unknown')
        <h2 id="sub-results-title">We don’t have “{{ $result['query'] }}” yet</h2>
        <p>We only show substitutes we have documented, so we don’t guess. @if ($result['suggestions']) Did you mean: <strong>{{ implode(', ', $result['suggestions']) }}</strong>? @endif</p>
        <p>Ingredients available now: {{ $catalog->pluck('name')->implode(', ') }}.</p>
    @elseif ($result['status'] === 'none')
        <h2 id="sub-results-title">No suitable {{ mb_strtolower($result['ingredient']->name) }} substitute found</h2>
        <p>None of our documented substitutes for {{ mb_strtolower($result['ingredient']->name) }} match this combination of cooking purpose and dietary preference. Try “General use” or “No preference” to see every option.</p>
    @else
        <h2 id="sub-results-title">{{ $result['ingredient']->name }} substitutes</h2>
        <p class="tt-muted">{{ $result['ingredient']->description }}</p>
        <div class="tt-sub-grid">
            @foreach ($result['items'] as $item)
                @include('tools.ingredient-substitution.components.substitute-card', ['item' => $item])
            @endforeach
        </div>
        <p class="tt-disclaimer">Ratios are guidelines and may need adjusting for your recipe. Dietary tags describe the substitute itself; always check product labels, as cross-contamination cannot be ruled out.
            @if ($result['ingredient']->guide_published) <a href="{{ route('ingredient-guides.show', $result['ingredient']->slug) }}">Read the full {{ mb_strtolower($result['ingredient']->name) }} substitution guide</a>.@endif
        </p>
    @endif
</section>
@include('partials.ad-slot', ['slot' => 'after_results'])
