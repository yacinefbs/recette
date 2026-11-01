@extends('pages.layout')

@section('content')
    <p>Type the ingredients you have at home, separated by commas, and we will suggest recipes that use them.</p>

    <form method="GET" action="{{ route('tools.what') }}" class="mb-4">
        <div class="input-group">
            <input type="text" name="ingredients" value="{{ $raw }}" class="form-control" placeholder="e.g. chicken, garlic, cream" aria-label="Ingredients" maxlength="200">
            <div class="input-group-append"><button class="btn btn-primary" type="submit">Find recipes</button></div>
        </div>
        <small class="text-muted">Tip: try main ingredients such as chicken, eggs, pasta, chocolate or potatoes.</small>
    </form>

    @if ($terms->isNotEmpty())
        <h2 class="h5 mb-3">{{ $results->count() }} recipe(s) found</h2>
        @forelse ($results as $r)
            @if ($loop->first)<div class="row">@endif
            <div class="col-md-6 col-lg-4 mb-4">
                <a href="{{ route('recipes.show', $r['recipe']) }}"><img src="{{ $r['recipe']->image }}" alt="{{ $r['recipe']->name }}" loading="lazy" width="300" height="200" style="height:180px;width:100%;object-fit:cover" class="mb-2"></a>
                <h3 class="h6 mb-1"><a href="{{ route('recipes.show', $r['recipe']) }}">{{ $r['recipe']->name }}</a></h3>
                <small class="text-muted">Matches {{ $r['matched']->count() }} of {{ $terms->count() }}: {{ $r['matched']->implode(', ') }}</small>
            </div>
            @if ($loop->last)</div>@endif
        @empty
            <p>No recipe matches yet. Try fewer or more general ingredients, or <a href="{{ route('recipes.index') }}">browse all recipes</a>.</p>
        @endforelse
    @endif

    <p class="mt-4">Need to convert a measurement? Use the <a href="{{ route('tools.converter') }}">Cooking Converter</a>.</p>
@endsection
