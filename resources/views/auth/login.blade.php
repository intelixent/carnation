@extends('layouts.auth')

@section('content')
<!-- Start Switcher -->

<style>
    .bg-primary-transparent-3 {
    background-color: rgb(219 117 164) !important;
    color: rgb(219 117 164) !important;
}
.bg-primary-transparent-3:hover {
    background-color: rgb(219 117 164) !important;
    color: rgb(219 117 164) !important;
}
    </style>
<!-- End Switcher -->

<div class="container-fluid custom-page">
    <div class="row bg-white">
        <!-- The image half -->
        <div class="col-md-6 col-lg-6 col-xl-7 d-none d-md-flex bg-primary-transparent-3">
            <div class="row w-100 mx-auto text-center">
                <div class="col-md-12 col-lg-12 col-xl-12 my-auto mx-auto w-100">
                    <!-- <img src="{{url('assets/web')}}/carnation_world.jpg"
                        class="my-auto ht-xl-80p wd-md-100p wd-xl-80p mx-auto" alt="logo"> -->
                </div>
            </div>
        </div>
        <!-- The content half -->
        <div class="col-md-6 col-lg-6 col-xl-5 bg-white">
            <div class="login d-flex align-items-center ">
                <!-- Demo content-->
                <div class="container p-0">
                    <div class="row">
                        <div class="col-md-10 col-lg-10 col-xl-9 mx-auto">
                            <div class="card-sigin">
                                <div class=" d-flex">
                                    <a href="{{url('')}}" class="header-logo"><img src="{{url('assets/web')}}/carnation_world.jpg"
                                            class="desktop-logo " alt="logo">
                                        <img src="{{url('assets/web')}}/carnation_world.jpg"
                                            class="desktop-white " alt="logo">
                                    </a>
                                </div>
                                <div class="card-sigin">
                                    <div class="main-signup-header">
                                        <h6 class="fw-medium mb-4 fs-17">Please sign in to continue.</h6>
                                        @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif

                                        @if(session('error'))
                                        <div class="alert alert-danger">
                                            {{ session('error') }}
                                        </div>
                                        @endif
                                        <form method="POST" action="{{ route('login') }}">
                                            @csrf
                                            <div class="form-group mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <input id="username" class="form-control @error('username') is-invalid @enderror"
                                                    name="username" value="{{ old('username') }}" required autocomplete="username" autofocus
                                                    placeholder="Enter your username" type="text">
                                                @error('username')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input id="password" class="form-control @error('password') is-invalid @enderror"
                                                    name="password" required autocomplete="current-password"
                                                    placeholder="Enter your password" type="password">
                                                @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                            <button type="submit" class="btn jb_btn btn-block w-100">Sign In</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End -->
            </div>
        </div><!-- End -->
    </div>
</div>
@endsection