@extends('layouts.app')
@section('pagetitle', "Dashboard")
@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">Hi, welcome back <span class="text-primary">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}! </span> </h4>

        </div>

    </div>
    <!-- End Page Header -->
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
    });
</script>
@endpush