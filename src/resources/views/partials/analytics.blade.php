<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-0ZPBSY263K"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}

  // Consent Mode v2: everything denied until the visitor chooses
  var tcConsent = null;
  try { tcConsent = localStorage.getItem('tt_consent'); } catch (e) {}
  var tcState = tcConsent === 'granted' ? 'granted' : 'denied';
  gtag('consent', 'default', {
    ad_storage: tcState,
    ad_user_data: tcState,
    ad_personalization: tcState,
    analytics_storage: tcState,
    wait_for_update: 500
  });

  gtag('js', new Date());

  gtag('config', 'G-0ZPBSY263K');
</script>
<!-- Google Tag Manager -->
<script>
  (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
  var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;
  j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-NT57L6CG');
</script>
<!-- End Google Tag Manager -->
@include('partials.consent')
