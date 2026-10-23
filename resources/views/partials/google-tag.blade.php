@if ($measurementId = config('services.google_analytics.measurement_id'))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        // Kunjungan halaman dikirim dari resources/js/lib/analytics.js dengan URL yang sudah
        // dibersihkan dari token (pratinjau, kode tamu, patungan, reset password).
        gtag('config', @json($measurementId), { send_page_view: false });
    </script>
@endif
