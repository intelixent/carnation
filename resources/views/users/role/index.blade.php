@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
<style>
    .card .card-header {
        background-color: #fdb71473 !important;
    }
</style>
<div class="container-fluid">
    <!-- BreadCrumbs -->
    <!-- <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">@yield('pagetitle')</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_main_title}}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{$page_title}}</li>
                </ol>
            </nav>
        </div>
    </div> -->
    <!-- BreadCrumbs -->

    <div class="modal fade" id="detail_modal"></div>

    <!-- row -->
    <div class="row mt-2">
        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif

        <div class="col-xl-12">
            <div class="card">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-dark mb-0">Role Master</h5>
                    @if($isSuperAdmin || auth()->user()->hasPermissionTo('create-role'))
                    <a href="{{ route('role_add') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Role
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="roles-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $key => $role)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $role->name }}</td>
                                    <td>{{ $role->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                Action
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                <li><a class="dropdown-item view-role" data-id="{{ $role->id }}" href="javascript:void(0);">View</a></li>
                                                <li><a class="dropdown-item edit-role" href="{{ route('role_edit', ['id' => $role->id]) }}">Edit</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- row closed -->
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(e) {
        $('#roles-table').DataTable();

        $(document).on('click', '.view-role', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('get_role_details')}}",
                method: 'POST',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#detail_modal").html(response);
                    $("#detail_modal").modal('show');
                }
            });
        });
    });
</script>
@endpush