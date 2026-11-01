@extends('pages.layout')

@php($wide = true)

@section('hero')
    <section class="tt-hero tt-hero--page">
        <div class="container">
            <span class="tt-eyebrow">Substitution guide</span>
            <h1>What Can I Use Instead of {{ $ingredient->name }}?</h1>
            <p class="tt-lead">{{ $seo['description'] }}</p>
            <ul class="tt-crumbs">
                <li><a href="/">Home</a></li>
                <li><a href="{{ route('tools.ingredient-substitution') }}">Ingredient Substitution Tool</a></li>
                <li aria-current="page">{{ $ingredient->name }}</li>
            </ul>
        </div>
    </section>
@endsection

@section('content')
    <article class="tt-prose tt-sub-article">
        <p>{{ $ingredient->guide_intro ?: $ingredient->description }}</p>
        <p><a class="tt-btn tt-btn--primary" href="{{ route('tools.ingredient-substitution') }}">Try the substitution tool</a></p>
    </article>

    @include('partials.ad-slot', ['slot' => 'guide'])

    <h2 class="tt-sub-h2">Best {{ mb_strtolower($ingredient->name) }} substitutes</h2>
    <div class="tt-sub-grid">
        @foreach ($items as $i => $s)
            @include('tools.ingredient-substitution.components.substitute-card', ['item' => ['model' => $s, 'best' => $i === 0, 'scaled' => null]])
        @endforeach
    </div>
    <p class="tt-disclaimer">Ratios are guidelines and may need adjusting for your recipe. Dietary tags describe the substitute itself; always check product labels, as cross-contamination cannot be ruled out.</p>

    @if ($ingredient->faqs)
        <article class="tt-prose tt-sub-article">
            <h2>Frequently asked questions</h2>
            @foreach ($ingredient->faqs as $faq)
                <h3>{{ $faq['q'] }}</h3>
                <p>{{ $faq['a'] }}</p>
            @endforeach
        </article>
    @endif

    <article class="tt-prose tt-sub-article">
        <h2>More substitution guides</h2>
        <ul>
            @foreach ($guides as $g)
                <li><a href="{{ route('ingredient-guides.show', $g->slug) }}">What can I use instead of {{ mb_strtolower($g->name) }}?</a></li>
            @endforeach
            <li><a href="{{ route('tools.converter') }}">Cooking Converter</a></li>
            <li><a href="{{ route('recipes.index') }}">Browse all recipes</a></li>
        </ul>
    </article>

    @include('partials.share', ['url' => route('ingredient-guides.show', $ingredient->slug), 'title' => "Substitutes for {$ingredient->name}"])
@endsection
