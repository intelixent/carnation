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

    .size-table {
        margin-top: 20px;
    }

    .size-table input[type="number"] {
        width: 100%;
        min-width: 80px;
    }
    
    .size-table th {
        text-align: center;
        vertical-align: middle;
    }
    
    .size-table td {
        text-align: center;
        vertical-align: middle;
    }
</style>
@endpush

<div class="container-fluid">
    <!-- BreadCrumbs -->
    <!-- 
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">@yield('pagetitle')</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_main_title}}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{$page_child_title}}</li>
                </ol>
            </nav>
        </div>
    </div> 
    -->
    <!-- BreadCrumbs -->

    <!-- row -->
    <div class="row mt-2">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <div class="card-title text-white">
                        Add Job Order
                    </div>
                </div>
                <div class="card-body">
                    <form id="JobOrderForm">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <label for="vendor_id">Select Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="job_no" name="job_no" placeholder="Job No">
                                    <label for="job_no">Job No <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="style" name="style" placeholder="Style">
                                    <label for="style">Style <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="color" name="color" placeholder="Color">
                                    <label for="color">Color <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3" id="type_row" style="display: none;">
                            <div class="col-sm-6">
                                <label for="type">Type <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="type" name="type">
                                    <option value="">Select Type</option>
                                    <option value="Junior">Junior</option>
                                    <option value="Men">Men</option>
                                </select>
                            </div>
                        </div>

                        <div class="row size-table" id="size_table_container" style="display: none;">
                            <div class="col-12">
                                <h5>Size Quantities</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="size_table">
                                        <thead class="bg-light">
                                            <tr id="size_header_row">
                                                <!-- Size headers will be added here -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="size_qty_row">
                                                <!-- Quantity inputs will be added here -->
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-sm-12">
                                <button class="btn btn-primary" type="submit" id="submit_btn" style="float:right">
                                    <i class="fas fa-save me-1"></i>Submit
                                </button>
                            </div>
                        </div>
                    </form>
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

        // Initialize Select2
        $('.select2').select2({
            width: '100%'
        });

        // Show/hide type field based on vendor selection
        $('#vendor_id').on('change', function() {
            var vendorId = $(this).val();

            if (vendorId == '1') {
                $('#type_row').show();
                $('#type').rules('add', {
                    required: true,
                    messages: {
                        required: "Please select type"
                    }
                });
                // Don't load sizes yet - wait for type selection
                $('#size_table_container').hide();
                $('#size_header_row').html('');
                $('#size_qty_row').html('');
            } else {
                $('#type_row').hide();
                $('#type').val('').trigger('change'); // Trigger change for select2
                if ($("#JobOrderForm").data('validator')) {
                    $('#type').rules('remove');
                }
                
                // Load sizes immediately for vendors other than 1
                if (vendorId) {
                    loadSizes();
                } else {
                    $('#size_table_container').hide();
                    $('#size_header_row').html('');
                    $('#size_qty_row').html('');
                }
            }
        });

        // Load sizes when type changes (only for vendor_id = 1)
        $('#type').on('change', function() {
            var vendorId = $('#vendor_id').val();
            var type = $(this).val();
            
            // Only load sizes if vendor is 1 and type is selected, or if vendor is not 1
            if (vendorId == '1' && type) {
                loadSizes();
            } else if (vendorId == '1' && !type) {
                // Hide table if vendor is 1 but no type selected
                $('#size_table_container').hide();
                $('#size_header_row').html('');
                $('#size_qty_row').html('');
            }
        });

        function loadSizes() {
            var vendorId = $('#vendor_id').val();
            var type = $('#type').val();

            // For vendor_id = 1, type must be selected
            // For other vendors, type is not required
            if (vendorId == '1' && !type) {
                $('#size_table_container').hide();
                $('#size_header_row').html('');
                $('#size_qty_row').html('');
                return;
            }

            $.ajax({
                url: "{{ route('get_sizes_by_vendor') }}",
                method: 'POST',
                data: {
                    vendor_id: vendorId,
                    type: type,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success && response.sizes.length > 0) {
                        var headerHtml = '';
                        var qtyHtml = '';
                        
                        $.each(response.sizes, function(index, size) {
                            // Add size as column header
                            headerHtml += '<th>' + size.size + '</th>';
                            
                            // Add quantity input below the size
                            qtyHtml += '<td><input type="number" class="form-control" name="qty_' + size.id + '" min="0" value="0" placeholder="Qty"></td>';
                        });
                        
                        $('#size_header_row').html(headerHtml);
                        $('#size_qty_row').html(qtyHtml);
                        $('#size_table_container').show();
                    } else {
                        $('#size_table_container').hide();
                        $('#size_header_row').html('');
                        $('#size_qty_row').html('');
                        Swal.fire({
                            icon: 'info',
                            title: 'No Sizes Found',
                            text: 'No sizes available for the selected vendor' + (type ? ' and type' : '') + '.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not load sizes. Please try again.'
                    });
                }
            });
        }

        // Form validation
        $("#JobOrderForm").validate({
            ignore: [], // Enable validation for hidden select2 elements
            rules: {
                vendor_id: {
                    required: true,
                },
                job_no: {
                    required: true,
                },
                style: {
                    required: true,
                },
                color: {
                    required: true,
                }
            },
            messages: {
                vendor_id: {
                    required: "Please select vendor",
                },
                job_no: {
                    required: "Please enter job no",
                },
                style: {
                    required: "Please enter style",
                },
                color: {
                    required: "Please enter color",
                }
            },
            errorElement: 'span',
            errorClass: 'error',
            errorPlacement: function(error, element) {
                if (element.hasClass('select2-hidden-accessible')) {
                    error.insertAfter(element.next('.select2-container'));
                } else {
                    error.insertAfter(element);
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
                    url: "{{ route('job_order_store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 3000
                            }).then(() => {
                                window.location.href = "{{ route('job_order_master') }}";
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
    });
</script>
@endpush