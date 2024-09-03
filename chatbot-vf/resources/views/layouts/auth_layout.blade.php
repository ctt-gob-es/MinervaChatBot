<!DOCTYPE html>
<html>
<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Login | Alicante</title>
    <meta property="og:image" content="images/logo-azu_peq-150x150.png">
    <link rel="icon" href="images/logo-azu_peq-150x150.png" type="image/x-icon">
    <meta name="keyword" content="CoreUI,Bootstrap,Admin,Template,InfyOm,Open,Source,jQuery,CSS,HTML,RWD,Dashboard">
    <!-- PWA  -->
    <meta name="theme-color" content="#009ef7"/>
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-30x30.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <!-- Bootstrap-->
    <!-- Theme style -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/sass/custom-style.scss'])
    @vite(['resources/css/login.css'])
</head>
<body class="app flex-row align-items-center">
@yield('content')
<!-- CoreUI and necessary plugins-->
<!-- <script>
    $(document).ready(function () {
        $('.alert').delay(4000).slideUp(300)
    })
    if (!navigator.serviceWorker.controller) {
        navigator.serviceWorker.register("/sw.js").then(function (reg) {
            console.log("Service worker has been registered for scope: " + reg.scope);
        });
    }
</script> -->
@yield('page_js')
@yield('scripts')
</body>
</html>
