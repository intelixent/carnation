@extends('layouts.app')
@section('pagetitle', $page_title)
@section('content')
@push('styles')
<style>
    .error-border {
        border: 1px solid #dc3545 !important;
    }

    .error {
        color: #dc3545;
        font-size: 80%;
        margin-top: 0.25rem;
        display: block;
    }

    .checkbox-group {
        position: relative;
        padding-bottom: 20px;
    }

    .checkbox-group .error {
        position: absolute;
        bottom: 0;
        left: 0;
    }

    .form-check {
        transition: none;
        transform: none !important;
    }

    .d-flex.flex-column.gap-2 {
        position: relative;
    }
</style>

<div class="container-fluid">
    <!-- BreadCrumbs -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">@yield('pagetitle')</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_main_title}}</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_title}}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{$page_child_title}}</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- BreadCrumbs -->

    <div class="modal fade" id="add_modal"></div>
    <div class="modal fade" id="detail_modal"></div>
    <div class="modal fade" id="edit_modal"></div>

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Vendor Master
                    </div>
                </div>
                <div class="card-body">
                    @if($isSuperAdmin ||auth()->user()->hasDirectPermission('create-vendor'))
                    <div class="mb-3 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary add-vendor">
                            Add Vendor
                        </button>
                    </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100 dataTable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Gst</th>
                                    <th>Actions</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendors as $key => $vendor)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $vendor->name }}</td>
                                    <td>
                                        <strong>Name:</strong> {{ $vendor->mobile }}<br>
                                        <strong>Mobile:</strong> {{ $vendor->email }}<br>
                                    </td>
                                    <td>
                                        <strong>No:</strong> {{ $vendor->gst_no }}<br>
                                        <strong>State:</strong> {{ $vendor->state->name }}<br>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                Action
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                @if($isSuperAdmin ||auth()->user()->hasDirectPermission('view-vendor'))
                                                <li><a class="dropdown-item view-vendor" data-id="{{ $vendor->id }}" href="javascript:void(0);">View</a></li>
                                                @endif
                                                @if($isSuperAdmin ||auth()->user()->hasDirectPermission('edit-vendor'))
                                                <li><a class="dropdown-item edit-vendor" data-id="{{ $vendor->id }}" href="javascript:void(0);">Edit</a></li>
                                                @endif
                                                @if($isSuperAdmin ||auth()->user()->hasDirectPermission('delete-vendor'))
                                                <li><a class="dropdown-item delete-vendor" data-id="{{ $vendor->id }}" href="javascript:void(0);">Delete</a></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            @php
                                            $hasPermission = $isSuperAdmin || auth()->user()->hasDirectPermission('status-vendor');
                                            @endphp
                                            <input class="form-check-input status-switch" type="checkbox" data-id="{{ $vendor->id }}" data-has-permission="{{ $hasPermission ? 'true' : 'false' }}" {{ $vendor->status == 0 ? 'checked' : '' }}>
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
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var table = $(".dataTable").DataTable({
            "order": [
                [0, "asc"]
            ]
        });

        $(document).on('click', '.add-vendor', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('vendor_add')}}",
                method: 'POST',
                success: function(response) {
                    $("#add_modal").html(response);
                    initValidation();
                    $("#add_modal").modal('show');
                }
            });
        });

        $(document).on('click', '.view-vendor', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('get_vendor_details')}}",
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

        $(document).on('click', '.edit-vendor', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('vendor_edit')}}",
                method: 'POST',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#edit_modal").html(response);
                    initValidation();
                    $("#edit_modal").modal('show');
                }
            });
        });

        $(document).on('click', '.delete-vendor', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action will permanently delete the vendor!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('vendor_delete') }}",
                        method: 'POST',
                        data: {
                            id: id,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Vendor has been deleted.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Something went wrong.',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'Could not delete the vendor. Please try again later.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        $(document).on('change', '.status-switch', function() {
            var id = $(this).data('id');
            var status = $(this).is(':checked') ? 0 : 1;
            var hasPermissionAttr = $(this).attr('data-has-permission');
            var hasPermission = hasPermissionAttr === 'true';

            if (!hasPermission) {
                $(this).prop('checked', !$(this).is(':checked'));

                Swal.fire({
                    title: 'Permission Denied',
                    text: 'You do not have permission to change vendor status.',
                    icon: 'error',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
                return;
            }

            $.ajax({
                url: "{{ route('vendor_update_status') }}",
                type: 'POST',
                data: {
                    id: id,
                    status: status,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        $.toast({
                            heading: 'Success',
                            text: 'Status updated successfully',
                            position: 'top-right',
                            bgColor: '#2ecc71',
                            textColor: 'white',
                            hideAfter: 3000,
                            stack: 6
                        });
                    } else {
                        $.toast({
                            heading: 'Error',
                            text: 'Error updating status',
                            position: 'top-right',
                            bgColor: '#e74c3c',
                            textColor: 'white',
                            hideAfter: 3000,
                            stack: 6
                        });
                    }
                },
                error: function() {
                    $.toast({
                        heading: 'Error',
                        text: 'Error updating status',
                        position: 'top-right',
                        bgColor: '#e74c3c',
                        textColor: 'white',
                        hideAfter: 3000,
                        stack: 6
                    });
                }
            });
        });

        function initValidation() {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('.modal-body')
            });

            $.validator.addMethod("Mobile", function(value, element) {
                return this.optional(element) || /^[6-9]\d{9}$/.test(value);
            }, "Please enter valid mobile number");

            $.validator.addMethod("validName", function(value, element) {
                return this.optional(element) || /^[A-Za-z]+(?: [A-Za-z]+)*$/.test(value);
            }, "Please enter valid name (only letters and single spaces between words)");

            $.validator.addMethod("prefixValidation", function(value, element) {
                return this.optional(element) || /^[a-zA-Z0-9-]+$/.test(value);
            }, "Prefix can only contain letters, numbers, and hyphens");

            $("#VendorAddForm").validate({
                rules: {
                    name: {
                        required: true,
                        validName: true
                    },
                    mobile: {
                        required: true,
                        Mobile: true,
                        minlength: 10,
                        maxlength: 10
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    address: {
                        required: true
                    },
                    gst_no: {
                        required: true
                    },
                    state_id: {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: "Please enter vendor name",
                        validName: "Please enter a valid name (only letters and single spaces between words)"
                    },
                    mobile: {
                        required: "Please enter mobile number",
                        Mobile: "Please enter a valid mobile number",
                        minlength: "Mobile number must be 10 digits",
                        maxlength: "Mobile number must be 10 digits"
                    },
                    email: {
                        required: "Please enter email address",
                        email: "Please enter a valid email address"
                    },
                    address: {
                        required: "Please enter address"
                    },
                    gst_no: {
                        required: "Please enter GST No"
                    },
                    state_id: {
                        required: "Please select a State"
                    }
                },
                errorElement: 'span',
                errorClass: 'error',
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('error-border');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error-border');
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('vendor_store') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $("#add_modal").modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                    timer: 3000
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'An error occurred'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while submitting the form'
                            });
                        }
                    });
                    return false;
                }
            });

            $("#VendorEditForm").validate({
                rules: {
                    name: {
                        required: true,
                        validName: true
                    },
                    mobile: {
                        required: true,
                        Mobile: true,
                        minlength: 10,
                        maxlength: 10
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    address: {
                        required: true
                    },
                    gst_no: {
                        required: true
                    },
                    state_id: {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: "Please enter vendor name",
                        validName: "Please enter a valid name (only letters and single spaces between words)"
                    },
                    mobile: {
                        required: "Please enter mobile number",
                        Mobile: "Please enter a valid mobile number",
                        minlength: "Mobile number must be 10 digits",
                        maxlength: "Mobile number must be 10 digits"
                    },
                    email: {
                        required: "Please enter email address",
                        email: "Please enter a valid email address"
                    },
                    address: {
                        required: "Please enter address"
                    },
                    gst_no: {
                        required: "Please enter GST No"
                    },
                    state_id: {
                        required: "Please select a State"
                    }
                },
                errorElement: 'span',
                errorClass: 'error',
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('error-border');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error-border');
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('vendor_update') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $("#edit_modal").modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Vendor updated successfully',
                                    timer: 3000
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Could not update vendor'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while updating the vendor'
                            });
                        }
                    });
                    return false;
                }
            });
        }
    });
</script>
@endpush