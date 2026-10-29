@extends('pages.layout')

@section('content')
    <p><em>Last updated: {{ date('F j, Y') }}</em></p>
    <p>This policy explains what information {{ $site }} ("we", "us") collects when you visit this website and how it is used.</p>

    <h2 class="h4 mt-4">Information we collect</h2>
    <p>We do not ask you to create an account and we do not collect personal data through forms. Our servers may log technical data such as IP address, browser type, pages visited and date/time of the request, for security and statistics purposes.</p>

    <h2 class="h4 mt-4">Cookies and similar technologies</h2>
    <p>We use cookies and similar technologies, mainly from third-party services described below, to measure audience and display advertising. Where required by law (for example in the European Economic Area and the United Kingdom), these cookies are only used with your consent.</p>

    <h2 class="h4 mt-4">Google Analytics</h2>
    <p>We use Google Analytics to understand how visitors use the site (pages viewed, time on site, approximate location). Google Analytics uses cookies to collect this information in an aggregated form. More information: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">How Google uses data when you use our partners' sites</a>.</p>

    <h2 class="h4 mt-4">Google AdSense and advertising</h2>
    <p>We use Google AdSense to display ads. Google and its partners, including third-party vendors, use cookies to serve ads based on your previous visits to this and other websites. Google's use of advertising cookies enables it and its partners to serve ads to you based on your visit to our site and/or other sites on the Internet.</p>
    <p>You can opt out of personalised advertising by visiting <a href="https://adssettings.google.com" target="_blank" rel="noopener">Google Ads Settings</a>, or learn how to opt out of third-party vendors' use of cookies at <a href="https://www.aboutads.info" target="_blank" rel="noopener">aboutads.info</a>.</p>

    <h2 class="h4 mt-4">Your rights</h2>
    <p>Depending on where you live (for example under the GDPR), you may have the right to access, correct or delete your personal data, to object to its processing, and to withdraw your consent at any time. You can also control cookies through your browser settings.</p>

    <h2 class="h4 mt-4">External links</h2>
    <p>This site may contain links to other websites. We are not responsible for their content or privacy practices.</p>

    <h2 class="h4 mt-4">Contact</h2>
    <p>For any question about this policy, write to <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.</p>
@endsection
