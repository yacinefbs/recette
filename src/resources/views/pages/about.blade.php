@extends('pages.layout')

@section('content')
    <p>{{ $site }} is a cooking website that brings together easy and delicious recipes. Each recipe comes with photos, a full ingredients list, step-by-step instructions and preparation times, so you can cook with confidence every day.</p>
    <h2 class="h4 mt-4">Where our recipes come from</h2>
    <p>Recipes are collected from the Tasty API and regularly refreshed. We organise them so they are easy to browse and search by keyword, such as chicken, pasta, salad, dessert or soup.</p>
    <h2 class="h4 mt-4">Our goal</h2>
    <p>Make home cooking simple: clear ingredients, clear steps and no clutter. Browse the <a href="{{ route('recipes.index') }}">full recipe list</a> to find your next dish.</p>
    <p>Questions or suggestions? <a href="{{ route('pages.show', 'contact') }}">Get in touch</a>.</p>
@endsection
