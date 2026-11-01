@extends('pages.layout')

@section('content')
    <p>{{ $site }} exists to help you find easy recipes and cook them with confidence. This page explains where our content comes from, what we add to it and how we handle mistakes.</p>

    <h2 class="h4 mt-4">Sources and credits</h2>
    <p>Recipes, ingredient lists, instructions and photos are provided through the Tasty API, and remain the property of their respective owners. We organise them into categories, make them searchable and add our own guidance around them. If you are a rights holder and want content credited or removed, <a href="{{ route('pages.show', 'contact') }}">contact us</a> and we will act promptly.</p>

    <h2 class="h4 mt-4">What we add</h2>
    <ul>
        <li>Category pages with practical cooking tips.</li>
        <li>Structured preparation times, servings and nutrition information when the source provides them.</li>
        <li>Related recipes to help you find what to cook next.</li>
        <li>Free tools such as the <a href="{{ route('tools.converter') }}">Cooking Converter</a> and <a href="{{ route('tools.what') }}">What Can I Cook?</a>.</li>
    </ul>

    <h2 class="h4 mt-4">What we do not claim</h2>
    <p>Recipes are not individually tested by our team unless a page says so. Cooking times and temperatures vary by oven and equipment, so use them as a guide and check that food is cooked through. Nutrition values are estimates provided by the source and are not medical advice. If you have allergies or dietary requirements, always check the ingredients yourself.</p>

    <h2 class="h4 mt-4">Corrections</h2>
    <p>Found an error in a recipe, a broken link or a wrong image? Tell us through the <a href="{{ route('pages.show', 'contact') }}">contact page</a> with the page address and we will review and fix it as quickly as we can.</p>

    <h2 class="h4 mt-4">Advertising</h2>
    <p>The site is supported by advertising. Ads never change how recipes are written or ranked. See our <a href="{{ route('pages.show', 'privacy-policy') }}">Privacy Policy</a> for how cookies are used.</p>
@endsection
