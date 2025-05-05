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
    <div class="modal fade" id="reporting_modal"></div>
    <div class="modal fade" id="password_modal"></div>

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
                    <h5 class="card-title text-dark mb-0">User Master</h5>
                    @if($isSuperAdmin || auth()->user()->hasPermissionTo('create-user'))
                    <a href="{{ route('user_add') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> User
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Roles</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $i=1;
                                @endphp
                                @foreach($users as $user)
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>{{ $user->full_name  }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                        <span class="badge bg-primary">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $user->created_at->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                Action
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                <li><a class="dropdown-item view-user" data-id="{{ $user->id }}" href="javascript:void(0);">View</a></li>
                                                <li><a class="dropdown-item edit-user" href="{{ route('user_edit', ['id' => $user->id]) }}">Edit</a></li>
                                                <li><a class="dropdown-item edit-user-password" data-id="{{ $user->id }}" href="javascript:void(0);">Password Change</a></li>
                                                <li><a class="dropdown-item delete-user" data-id="{{ $user->id }}" href="javascript:void(0);">Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @php $i++; @endphp
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
<script type="text/javascript">
    $(document).ready(function(e) {
        $('#users-table').DataTable();

        $(document).on('click', '.view-user', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('get_user_details')}}",
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

        $(document).on('click', '.edit-user-password', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('get_user_password')}}",
                method: 'POST',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#password_modal").html(response);
                    $("#password_modal").modal('show');
                }
            });
        });

        $(document).on('click', '.delete-user', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('user_delete') }}",
                        method: 'POST',
                        data: {
                            id: id,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                );
                                location.reload();
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'There was an issue deleting the user.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        $(document).on('submit', '#change-password-form', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();

            let password = $('#password').val();
            let confirmPassword = $('#password-confirm').val();

            if (password !== confirmPassword) {
                Swal.fire(
                    'Error!',
                    'Passwords do not match',
                    'error'
                );
                return;
            }

            $.ajax({
                url: "{{ route('user_password_update') }}",
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Success!',
                            response.message,
                            'success'
                        );
                        $("#password_modal").modal('hide');
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        Swal.fire(
                            'Error!',
                            Object.values(xhr.responseJSON.errors).flat().join('\n'),
                            'error'
                        );
                    } else {
                        Swal.fire(
                            'Error!',
                            'An error occurred while updating the password',
                            'error'
                        );
                    }
                }
            });
        });
    });
</script>
@endpush