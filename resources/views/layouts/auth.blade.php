<!doctype html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Favicon -->
    <link rel="icon" href="{{url('assets/web')}}/carnation_world.jpg" type="image/x-icon">

    <!-- Main Theme Js -->
    <script src="{{url('assets/web')}}/js/authentication-main.js"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="{{url('assets/web')}}/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet" >

    <!-- Style Css -->
    <link href="{{url('assets/web')}}/css/styles.min.css" rel="stylesheet" >

    <!-- Icons Css -->
    <link href="{{url('assets/web')}}/css/icons.min.css" rel="stylesheet" >

    <!-- Scripts -->
    <link href="{{url('assets/web')}}/css/carnation.css" rel="stylesheet" >
   
</head>
<body>
    <div id="app">
    @yield('content')
    </div>
</body>
<!-- Bootstrap JS -->
<script src="{{url('assets/web')}}/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Show Password JS -->
<script src="{{url('assets/web')}}/js/show-password.js"></script>
</html>
