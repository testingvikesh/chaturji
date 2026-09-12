<link rel="manifest" href="{{ rtrim(request()->root(), '/') }}/manifest.webmanifest">
<meta name="theme-color" content="#1a367c">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Chaturji') }}">
<meta name="pwa-sw" content="{{ rtrim(request()->root(), '/') }}/sw.js">
<link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
