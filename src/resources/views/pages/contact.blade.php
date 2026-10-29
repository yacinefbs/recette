@extends('pages.layout')

@section('content')
    <p>We would love to hear from you: questions about a recipe, suggestions, partnership requests or content removal requests.</p>
    <p><strong>Email:</strong> <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>
    <p>We try to reply within a few business days.</p>
@endsection
