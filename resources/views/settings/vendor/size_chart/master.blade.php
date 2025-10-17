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
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <div class="card-title text-white">
                        Size Chart Master
                    </div>
                    <div class="card-options">
                        @if($isSuperAdmin || auth()->user()->hasDirectPermission('add-vendor'))
                        <button class="btn btn-sm btn-light add-size" type="button">
                            <i class="fas fa-plus me-1"></i>Add Size Chart
                        </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100" id="sizeChartTable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Vendor</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $index = 1; @endphp
                                @if($vendors->isNotEmpty())
                                @foreach($vendors as $key => $items)
                                @if($items->isNotEmpty())
                                @php
                                $item = $items->first();
                                [$vendor_id, $type_key] = explode('-', $key);
                                $type = $type_key === 'default' ? null : $type_key;
                                @endphp
                                <tr>
                                    <td>{{ $index++ }}</td>
                                    <td><strong>{{ $item->vendor->name ?? 'N/A' }}</strong></td>
                                    <td>
                                        @if($vendor_id == 1 && $type)
                                        <span class="badge bg-info">{{ $type }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton{{ $index }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                Action
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $index }}">
                                                @if($isSuperAdmin || auth()->user()->hasDirectPermission('view-vendor'))
                                                <li><a class="dropdown-item view-size" data-vendor-id="{{ $vendor_id }}" data-type="{{ $type }}" href="javascript:void(0);">View</a></li>
                                                @endif
                                                @if($isSuperAdmin || auth()->user()->hasDirectPermission('edit-vendor'))
                                                <li><a class="dropdown-item edit-size" data-vendor-id="{{ $vendor_id }}" data-type="{{ $type }}" href="javascript:void(0);">Edit</a></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                                @endif
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

        // Initialize DataTable with proper error handling
        function initializeDataTable() {
            // Check if DataTable is already initialized
            if ($.fn.DataTable.isDataTable('#sizeChartTable')) {
                $('#sizeChartTable').DataTable().destroy();
            }

            // Validate table structure before initialization
            var headerColumns = $('#sizeChartTable thead th').length;
            var hasData = $('#sizeChartTable tbody tr').length > 0;
            var validStructure = true;

            if (hasData) {
                $('#sizeChartTable tbody tr').each(function(index) {
                    var rowColumns = $(this).find('td').length;
                    var hasColspan = $(this).find('td[colspan]').length > 0;

                    if (!hasColspan && rowColumns !== headerColumns) {
                        console.warn('Row ' + index + ' has ' + rowColumns + ' columns but header has ' + headerColumns);
                        validStructure = false;
                    }
                });
            }

            if (validStructure) {
                try {
                    var table = $("#sizeChartTable").DataTable({
                        "processing": true,
                        "order": [
                            [0, "asc"]
                        ],
                        "pageLength": 25,
                        "responsive": true,
                        "columnDefs": [{
                            "targets": [3],
                            "orderable": false,
                            "searchable": false
                        }],
                        "language": {
                            "emptyTable": "No size charts found",
                            "zeroRecords": "No matching records found"
                        },
                        "drawCallback": function(settings) {
                            // Reinitialize any tooltips or other components after redraw
                            $('[data-bs-toggle="tooltip"]').tooltip();
                        }
                    });
                } catch (error) {
                    console.error('DataTable initialization error:', error);
                    // Fallback: show table without DataTable enhancements
                    $('#sizeChartTable').show();
                }
            } else {
                console.error('Table structure is invalid for DataTable initialization');
                $('#sizeChartTable').show();
            }
        }

        // Initialize DataTable on page load
        initializeDataTable();

        $(document).on('click', '.add-size', function() {
            $.ajax({
                url: "{{route('size_chart_add')}}",
                method: 'POST',
                success: function(response) {
                    $("#add_modal").html(response);
                    initAddValidation();
                    $("#add_modal").modal('show');
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not load add form'
                    });
                }
            });
        });

        $(document).on('click', '.view-size', function() {
            var vendor_id = $(this).data('vendor-id');
            var type = $(this).data('type');

            // Convert null/undefined to empty string
            type = type === null || type === undefined || type === 'null' ? '' : type;

            $.ajax({
                url: "{{route('get_size_chart_details')}}",
                method: 'POST',
                data: {
                    vendor_id: vendor_id,
                    type: type,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#detail_modal").html(response);
                    $("#detail_modal").modal('show');
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not load size chart details'
                    });
                }
            });
        });

        $(document).on('click', '.edit-size', function() {
            var vendor_id = $(this).data('vendor-id');
            var type = $(this).data('type');

            // Convert null/undefined to empty string
            type = type === null || type === undefined || type === 'null' ? '' : type;

            $.ajax({
                url: "{{route('size_chart_edit')}}",
                method: 'POST',
                data: {
                    vendor_id: vendor_id,
                    type: type,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#edit_modal").html(response);
                    initEditValidation();
                    $("#edit_modal").modal('show');
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not load edit form'
                    });
                }
            });
        });

        function initAddValidation() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#add_modal .modal-body')
            });

            // Show/hide type field and existing sizes
            $(document).on('change', '#vendor_id', function() {
                var vendorId = $(this).val();
                if (vendorId == '1') {
                    $('#type_row').show();
                    $('#type').rules('add', {
                        required: true,
                        messages: {
                            required: "Please select type"
                        }
                    });
                } else {
                    $('#type_row').hide();
                    $('#type').val('');
                    if ($("#SizeChartForm").data('validator')) {
                        $('#type').rules('remove');
                    }
                }
                updateExistingSizesDisplay();
            });

            $(document).on('change', '#type', function() {
                updateExistingSizesDisplay();
            });

            function updateExistingSizesDisplay() {
                var vendorId = $('#vendor_id').val();
                var type = $('#type').val() || 'default';

                if (!vendorId) {
                    $('#existing_sizes_container').hide();
                    return;
                }

                var key = vendorId + '-' + type;
                $('.existing-size-group').hide();
                $('#existing-sizes-' + key.replace(/[^a-zA-Z0-9]/g, '_')).show();
                $('#existing_sizes_container').show();
            }

            // Add size row
            $(document).on('click', '#add_size_row', function() {
                var newRow = `
                    <div class="input-group mb-2 size-row">
                        <input type="text" class="form-control" name="sizes[]" placeholder="Enter size" required>
                        <button type="button" class="btn btn-danger remove-size-row">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                $('#sizes_container').append(newRow);
            });

            // Remove size row
            $(document).on('click', '.remove-size-row', function() {
                $(this).closest('.size-row').remove();
            });

            // Initialize validation
            $("#SizeChartForm").validate({
                rules: {
                    vendor_id: {
                        required: true,
                    },
                    'sizes[]': {
                        required: true,
                    }
                },
                messages: {
                    vendor_id: {
                        required: "Please select vendor",
                    },
                    'sizes[]': {
                        required: "Please enter size",
                    }
                },
                errorElement: 'span',
                errorClass: 'error',
                errorPlacement: function(error, element) {
                    if (element.hasClass('select2-hidden-accessible')) {
                        error.insertAfter(element.next('.select2-container'));
                    } else {
                        error.insertAfter(element.closest('.input-group, .form-floating, .col-sm-6'));
                    }
                },
                highlight: function(element) {
                    $(element).addClass('error-border');
                    if ($(element).hasClass('select2-hidden-accessible')) {
                        $(element).next('.select2-container').find('.select2-selection').addClass('error-border');
                    }
                },
                unhighlight: function(element) {
                    $(element).removeClass('error-border');
                    if ($(element).hasClass('select2-hidden-accessible')) {
                        $(element).next('.select2-container').find('.select2-selection').removeClass('error-border');
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('size_chart_store') }}",
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
                        error: function(xhr) {
                            var errorMessage = 'An error occurred while submitting the form';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
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

        function initEditValidation() {
            // Add new size row
            $(document).on('click', '#add_new_size_row', function() {
                var newRow = `
                    <div class="input-group mb-2 new-size-row">
                        <input type="text" class="form-control" name="new_sizes[]" placeholder="Enter new size" required>
                        <button type="button" class="btn btn-danger remove-new-size-row">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                $('#new_sizes_container').append(newRow);
            });

            // Remove new size row
            $(document).on('click', '.remove-new-size-row', function() {
                $(this).closest('.new-size-row').remove();
            });

            // Delete existing size
            $(document).on('click', '.delete-existing-size', function() {
                var id = $(this).data('id');
                var row = $(this).closest('.existing-size-row');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the size!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('size_chart_delete') }}",
                            method: 'POST',
                            data: {
                                id: id,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    row.remove();
                                    Swal.fire('Deleted!', 'Size has been deleted.', 'success');
                                } else {
                                    Swal.fire('Error!', 'Something went wrong.', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Could not delete the size.', 'error');
                            }
                        });
                    }
                });
            });

            // Initialize validation
            $("#SizeChartEditForm").validate({
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('size_chart_update') }}",
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
                                    text: response.message,
                                    timer: 3000
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Could not update size chart'
                                });
                            }
                        },
                        error: function(xhr) {
                            var errorMessage = 'An error occurred while updating';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
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

        // Refresh DataTable after AJAX operations
        window.refreshDataTable = function() {
            if ($.fn.DataTable.isDataTable('#sizeChartTable')) {
                $('#sizeChartTable').DataTable().ajax.reload(null, false);
            } else {
                location.reload();
            }
        };
    });
</script>
@endpush