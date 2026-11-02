@extends('social.layout')
@section('title', 'Recipes to post')
@section('content')
@unless ($facebookReady)
    <div class="flash flash--err">Facebook is not configured: set <code>FACEBOOK_PAGE_ID</code> and <code>FACEBOOK_PAGE_TOKEN</code> in .env. You can still mark recipes as posted manually.</div>
@endunless

<div class="tabs">
    <a href="{{ route('social.index', ['status' => 'pending', 'q' => $q ?: null]) }}" class="{{ $status === 'pending' ? 'on' : '' }}">Not posted ({{ $counts['pending'] }})</a>
    <a href="{{ route('social.index', ['status' => 'posted', 'q' => $q ?: null]) }}" class="{{ $status === 'posted' ? 'on' : '' }}">Posted on Facebook ({{ $counts['posted'] }})</a>
</div>

<form method="get" class="card" style="margin-bottom:16px;display:flex;gap:8px">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search a recipe" aria-label="Search a recipe">
    <button class="btn" type="submit">Search</button>
</form>

<div class="card">
    @forelse ($recipes as $recipe)
        <div class="row">
            <img src="{{ $recipe->image }}" alt="{{ $recipe->name }}" loading="lazy">
            <div class="body">
                <h3><a href="{{ route('recipes.show', $recipe) }}" target="_blank" rel="noopener">{{ $recipe->name }}</a></h3>
                @if ($recipe->posted_facebook)
                    <span class="badge">Posted{{ $recipe->facebook_posted_at ? ' on '.$recipe->facebook_posted_at->format('Y-m-d H:i') : '' }}</span>
                @endif
                <div class="actions">
                    @unless ($recipe->posted_facebook)
                        <form method="post" action="{{ route('social.facebook.publish', $recipe) }}" style="width:100%">
                            @csrf
                            <details>
                                <summary>Edit the message</summary>
                                <textarea name="message" rows="4" maxlength="2000">{{ $publisher->message($recipe) }}</textarea>
                            </details>
                            <div class="actions">
                                <button class="btn btn--fb" type="submit" @disabled(! $facebookReady) onclick="return confirm('Post this recipe on the Facebook page?')">Post to Facebook</button>
                            </div>
                        </form>
                    @endunless
                    <form method="post" action="{{ route('social.facebook.toggle', $recipe) }}">
                        @csrf
                        <button class="btn btn--ghost" type="submit">{{ $recipe->posted_facebook ? 'Mark as not posted' : 'Mark as posted' }}</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <p class="muted">No recipes here.</p>
    @endforelse
</div>
<div class="pager">@if ($recipes->hasPages())
    <div class="actions">
        @if ($recipes->previousPageUrl())<a class="btn btn--ghost" href="{{ $recipes->previousPageUrl() }}">&larr; Previous</a>@endif
        <span class="muted" style="align-self:center">Page {{ $recipes->currentPage() }} / {{ $recipes->lastPage() }}</span>
        @if ($recipes->nextPageUrl())<a class="btn btn--ghost" href="{{ $recipes->nextPageUrl() }}">Next &rarr;</a>@endif
    </div>
@endif</div>
@endsection
