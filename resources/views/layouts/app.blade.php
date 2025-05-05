<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="dark" data-menu-styles="light" data-toggled="close">
<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {{ config('app.name', 'Laravel') }}  |  @yield('pagetitle') </title>

    @include('layouts.core.css')
    
@include('layouts.core.fonts')
</head>

<body>

   
    <!-- End Switcher -->
    <!-- Loader -->
   
    @include('layouts.core.loader')
    <!-- Loader -->

    <div class="page">
         <!-- app-header -->
         @include('layouts.core.header')
        <!-- /app-header -->
        <!-- Start::Off-canvas sidebar-->
        <!-- End::Off-canvas sidebar-->

        <!-- Start::app-sidebar -->
        @include('layouts.core.menu')
        <!-- End::app-sidebar -->
        <!-- Start::app-content -->
        <div class="main-content app-content">
        @yield('content')
        </div>
        <!-- End::app-content -->

        <!-- Footer Start -->
        @include('layouts.core.footer')
        <!-- Footer End -->

    </div>

    
    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow"><i class="las la-angle-double-up"></i></span>
    </div>
    <div id="responsive-overlay"></div>
    <!-- Scroll To Top -->

    @include('layouts.core.js')
    @stack('scripts')

</body>

</html>