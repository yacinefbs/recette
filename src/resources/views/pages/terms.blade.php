@extends('pages.layout')

@section('content')
    <p><em>Last updated: {{ date('F j, Y') }}</em></p>
    <p>By using {{ $site }} you agree to these terms.</p>

    <h2 class="h4 mt-4">Use of the website</h2>
    <p>The content of this website is provided for personal, non-commercial information and cooking inspiration. You agree not to misuse the site, attempt to disrupt it, or scrape it in an abusive way.</p>

    <h2 class="h4 mt-4">Recipes and food safety</h2>
    <p>Recipes are provided as-is. Cooking times, quantities and nutritional information are indicative. You are responsible for checking ingredients for allergies and following proper food-safety practices. Some recipes include alcohol and are intended only for people of legal drinking age.</p>

    <h2 class="h4 mt-4">Intellectual property</h2>
    <p>Recipes and images come from third-party sources, including the Tasty API, and remain the property of their respective owners. If you believe content infringes your rights, contact us at <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a> and we will review it promptly.</p>

    <h2 class="h4 mt-4">Advertising</h2>
    <p>The site displays advertising, notably through Google AdSense. See our <a href="{{ route('pages.show', 'privacy-policy') }}">Privacy Policy</a> for details.</p>

    <h2 class="h4 mt-4">Limitation of liability</h2>
    <p>We make no warranty about the accuracy or availability of the content and are not liable for any damage resulting from the use of the site or the recipes it contains.</p>

    <h2 class="h4 mt-4">Changes</h2>
    <p>We may update these terms at any time. Continued use of the site means you accept the updated terms.</p>
@endsection
