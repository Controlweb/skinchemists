{{-- Makes the admin panel installable. Injected into Filament's <head>. --}}
<link rel="manifest" href="{{ route('admin.pwa.manifest') }}">
<meta name="theme-color" content="#18181b">
<meta name="mobile-web-app-capable" content="yes">

{{-- iOS ignores the manifest: it needs its own meta tags and icon. --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="SC Admin">
<link rel="apple-touch-icon" href="{{ asset('uploads/admin-icon-192.png') }}">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('{{ route('admin.pwa.sw') }}');
        });
    }
</script>
