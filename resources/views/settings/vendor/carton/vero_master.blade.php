@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
<div class="container-fluid">
    <!-- BreadCrumbs -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">@yield('pagetitle')</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_main_title}}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{$page_title}}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="modal fade" id="add_modal"></div>
    <div class="modal fade" id="detail_modal"></div>
    <div class="modal fade" id="edit_modal"></div>

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <div class="card-title text-white">
                        Vero Modo
                    </div>
                    @if($isSuperAdmin || auth()->user()->hasPermissionTo('create-vendor-carton'))
                    <button type="button" class="btn btn-primary add-carton">
                        Add Carton
                    </button>
                    @endif
                </div>
                <div class="card-body">
                    <nav class="nav nav-style-6 nav-pills mb-3 nav-justified d-sm-flex d-block" role="tablist">
                        <a class="nav-link" href="{{route('carton_jack_master')}}">Jack Jones</a>
                        <a class="nav-link" href="{{route('carton_skecher_master')}}">Skecher</a>
                        <a class="nav-link" href="{{route('carton_puma_master')}}">Puma</a>
                        <a class="nav-link" href="{{route('carton_benetton_master')}}">Benetton</a>
                        <a class="nav-link" href="{{route('carton_selected_master')}}">Selected</a>
                        <a class="nav-link active" ata-bs-toggle="tab" role="tab" href="#nav-vero" aria-selected="true" href="{{route('carton_vero_master')}}">Vero Modo</a>
                    </nav>
                    <div class="tab-content">
                        <div class="tab-pane show active text-muted" id="nav-vero" role="tabpanel">
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered text-nowrap w-100 dataTable">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Name</th>
                                                    <th>Length</th>
                                                    <th>Breadth</th>
                                                    <th>Height</th>
                                                    <th>Weight</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($cartons as $key => $carton)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $carton ->name }}</td>
                                                    <td>{{ $carton ->length }}</td>
                                                    <td>{{ $carton ->breadth }}</td>
                                                    <td>{{ $carton ->height }}</td>
                                                    <td>{{ $carton ->weight }}</td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                                Action
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                                <li><a class="dropdown-item edit-carton" data-id="{{ $carton->id }}" href="javascript:void(0);">Edit</a></li>
                                                                <li><a class="dropdown-item delete-carton" data-id="{{ $carton->id }}" href="javascript:void(0);">Delete</a></li>
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize DataTable
        var table = $(".dataTable").DataTable({
            "order": [
                [0, "asc"]
            ]
        });

        $(document).on('click', '.add-carton', function() {
            var vendorId = $(this).data('vendor-id');
            $.ajax({
                url: "{{route('carton_add')}}",
                method: 'POST',
                data: {
                    vendor_id: vendorId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#add_modal").html(response);
                    initValidation();
                    initModalFunctions();
                    $("#add_modal").modal('show');
                }
            });
        });

        $(document).on('click', '.edit-carton', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('carton_edit')}}",
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

        $(document).on('click', '.delete-carton', function() {
            var id = $(this).data('id');
            var vendorId = $(this).data('vendor-id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action will permanently delete the carton!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('carton_delete') }}",
                        method: 'POST',
                        data: {
                            id: id,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Carton has been deleted.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Could not delete the carton. Please try again later.', 'error');
                        }
                    });
                }
            });
        });

        function initModalFunctions() {
            rowIndex = 0;

            addNewRow();

            $(document).off('click', '#add-row-btn').on('click', '#add-row-btn', function() {
                addNewRow();
            });

            $(document).off('click', '.remove-row').on('click', '.remove-row', function() {
                if ($('#carton-tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'At least one row is required!'
                    });
                }
            });
        }

        function addNewRow() {
            rowIndex++;
            const newRow = `
            <tr>
                <td>
                    <input type="text" name="cartons[${rowIndex}][name]" class="form-control carton-name" placeholder="Enter carton name" required>
                </td>
                <td>
                    <input type="number" name="cartons[${rowIndex}][length]" class="form-control carton-length" placeholder="Length" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="cartons[${rowIndex}][breadth]" class="form-control carton-breadth" placeholder="Breadth" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="cartons[${rowIndex}][height]" class="form-control carton-height" placeholder="Height" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="cartons[${rowIndex}][weight]" class="form-control carton-weight" placeholder="Weight" step="0.01" required>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </td>
            </tr>
        `;
            $('#carton-tbody').append(newRow);
        }

        function initValidation() {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('.modal-body')
            });

            // Add form validation
            $("#CartonAddForm").validate({
                rules: {
                    vendor_id: {
                        required: true
                    }
                },
                messages: {
                    vendor_id: {
                        required: "Please select a vendor"
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
                    // Check if at least one carton row exists
                    if ($('#carton-tbody tr').length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Warning',
                            text: 'Please add at least one carton!'
                        });
                        return false;
                    }

                    // Validate all carton fields
                    let isValid = true;
                    $('#carton-tbody input[required]').each(function() {
                        if (!$(this).val()) {
                            $(this).addClass('error-border');
                            isValid = false;
                        } else {
                            $(this).removeClass('error-border');
                        }
                    });

                    if (!isValid) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validation Error',
                            text: 'Please fill in all required fields!'
                        });
                        return false;
                    }

                    // Show loading state
                    $("#submit_btn").prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('carton_store') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            // Reset button state
                            $("#submit_btn").prop('disabled', false).html('Submit');

                            if (response.success) {
                                $("#add_modal").modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                    timer: 3000,
                                    showConfirmButton: false
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
                        error: function(xhr) {
                            // Reset button state
                            $("#submit_btn").prop('disabled', false).html('Submit');

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage
                            });
                        }
                    });
                    return false;
                }
            });

            // Edit form validation
            $("#CartonEditForm").validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 2
                    },
                    length: {
                        required: true,
                        min: 0.01
                    },
                    breadth: {
                        required: true,
                        min: 0.01
                    },
                    height: {
                        required: true,
                        min: 0.01
                    },
                    weight: {
                        required: true,
                        min: 0.01
                    }
                },
                messages: {
                    name: {
                        required: "Please enter carton name",
                        minlength: "Carton name must be at least 2 characters"
                    },
                    length: {
                        required: "Please enter length",
                        min: "Length must be greater than 0"
                    },
                    breadth: {
                        required: "Please enter breadth",
                        min: "Breadth must be greater than 0"
                    },
                    height: {
                        required: "Please enter height",
                        min: "Height must be greater than 0"
                    },
                    weight: {
                        required: "Please enter weight",
                        min: "Weight must be greater than 0"
                    }
                },
                errorElement: 'span',
                errorClass: 'error text-danger',
                errorPlacement: function(error, element) {
                    error.addClass('d-block');
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                },
                submitHandler: function(form) {
                    // Show loading state
                    $("#update_btn").prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('carton_update') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            // Reset button state
                            $("#update_btn").prop('disabled', false).html('Update Carton');

                            if (response.success) {
                                $("#edit_modal").modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                    timer: 3000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Could not update carton'
                                });
                            }
                        },
                        error: function(xhr) {
                            // Reset button state
                            $("#update_btn").prop('disabled', false).html('Update Carton');

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage
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