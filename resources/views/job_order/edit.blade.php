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

<div class="container-fluid">
    <!-- row -->
    <div class="row mt-2">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <div class="card-title text-white">
                        Edit Job Order - {{ $job_order->job_no }}
                    </div>
                </div>
                <div class="card-body">
                    <form id="JobOrderForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="job_order_id" value="{{ $job_order->id }}">

                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <label for="vendor_id">Select Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ $job_order->vendor_id == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="job_no" name="job_no" placeholder="Job No" value="{{ $job_order->job_no }}">
                                    <label for="job_no">Job No <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="style" name="style" placeholder="Style" value="{{ $job_order->style }}">
                                    <label for="style">Style <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="color" name="color" placeholder="Color" value="{{ $job_order->color }}">
                                    <label for="color">Color <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3" id="type_row" style="{{ $job_order->vendor_id == 1 ? 'display: block;' : 'display: none;' }}">
                            <div class="col-sm-6">
                                <label for="type">Type <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="type" name="type">
                                    <option value="">Select Type</option>
                                    <option value="Junior" {{ $job_order->type == 'Junior' ? 'selected' : '' }}>Junior</option>
                                    <option value="Men" {{ $job_order->type == 'Men' ? 'selected' : '' }}>Men</option>
                                </select>
                            </div>
                        </div>

                        <div class="row size-table" id="size_table_container" style="{{ $job_order->sizes && $job_order->sizes->count() > 0 ? 'display: block;' : 'display: none;' }}">
                            <div class="col-12">
                                <h5>Size Quantities</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="size_table">
                                        <thead class="bg-light">
                                            <tr id="size_header_row">
                                                @if($job_order->sizes && $job_order->sizes->count() > 0)
                                                @foreach($job_order->sizes as $job_size)
                                                <th style="text-align: center; vertical-align: middle; min-width: 100px; {{ isset($job_size->is_new) && $job_size->is_new ? 'background-color: #e8f5e8; border: 2px solid #28a745;' : '' }}">
                                                    {{ $job_size->size ?? $job_size->size->size }}
                                                    @if(isset($job_size->is_new) && $job_size->is_new)
                                                    <small class="badge bg-success ms-1">New</small>
                                                    @endif
                                                </th>
                                                @endforeach
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="size_qty_row">
                                                @if($job_order->sizes && $job_order->sizes->count() > 0)
                                                @foreach($job_order->sizes as $job_size)
                                                <td style="text-align: center; vertical-align: middle; {{ isset($job_size->is_new) && $job_size->is_new ? 'background-color: #e8f5e8;' : '' }}">
                                                    <input type="number" class="form-control {{ isset($job_size->is_new) && $job_size->is_new ? 'border-success' : '' }}"
                                                        name="qty_{{ $job_size->size_id ?? $job_size->id }}"
                                                        min="0"
                                                        value="{{ $job_size->qty }}"
                                                        placeholder="Qty"
                                                        style="width: 100%; min-width: 80px;"
                                                        {{ isset($job_size->is_new) && $job_size->is_new ? 'title="New size added to this vendor"' : '' }}>
                                                </td>
                                                @endforeach
                                                @endif
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-sm-12">
                                <a href="{{ route('job_order_master') }}" class="btn btn-secondary" style="float:left">
                                    <i class="fas fa-arrow-left me-1"></i>Back
                                </a>
                                <button class="btn btn-primary" type="submit" id="submit_btn" style="float:right">
                                    <i class="fas fa-save me-1"></i>Update
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

        // Initialize form validation
        initializeValidation();

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
                if (!$('#type').val()) {
                    $('#size_table_container').hide();
                    $('#size_tbody').html('');
                }
            } else {
                $('#type_row').hide();
                $('#type').val('').trigger('change');
                if ($("#JobOrderForm").data('validator')) {
                    $('#type').rules('remove');
                }

                // Load sizes immediately for vendors other than 1
                if (vendorId) {
                    loadSizes();
                } else {
                    $('#size_table_container').hide();
                    $('#size_tbody').html('');
                }
            }
        });

        // Load sizes when type changes (only for vendor_id = 1)
        $('#type').on('change', function() {
            var vendorId = $('#vendor_id').val();
            var type = $(this).val();

            // Only load sizes if vendor is 1 AND type is selected
            if (vendorId == '1' && type) {
                loadSizes();
            } else if (vendorId == '1' && !type) {
                // Hide table if vendor is 1 but no type selected
                $('#size_table_container').hide();
                $('#size_tbody').html('');
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

            // Store current quantities before reloading
            var currentQuantities = {};
            $('input[name^="qty_"]').each(function() {
                var sizeId = $(this).attr('name').replace('qty_', '');
                currentQuantities[sizeId] = $(this).val();
            });

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
                            // Get existing quantity or default to 0
                            var existingQty = currentQuantities[size.id] || '0';

                            // Check if this is a new size (not in original job order)
                            var isNewSize = !currentQuantities.hasOwnProperty(size.id) && Object.keys(currentQuantities).length > 0;

                            // Style for new sizes
                            var headerStyle = 'text-align: center; vertical-align: middle; min-width: 100px;';
                            var cellStyle = 'text-align: center; vertical-align: middle;';
                            var inputClass = 'form-control';

                            if (isNewSize) {
                                headerStyle += ' background-color: #e8f5e8; border: 2px solid #28a745;';
                                cellStyle += ' background-color: #e8f5e8;';
                                inputClass += ' border-success';
                            }

                            // Add size as column header
                            headerHtml += '<th style="' + headerStyle + '">' + size.size;
                            if (isNewSize) {
                                headerHtml += ' <small class="badge bg-success ms-1">New</small>';
                            }
                            headerHtml += '</th>';

                            // Add quantity input below the size
                            qtyHtml += '<td style="' + cellStyle + '">';
                            qtyHtml += '<input type="number" class="' + inputClass + '" name="qty_' + size.id + '" min="0" value="' + existingQty + '" placeholder="Qty" style="width: 100%; min-width: 80px;"';
                            if (isNewSize) {
                                qtyHtml += ' title="New size added to this vendor"';
                            }
                            qtyHtml += '></td>';
                        });

                        $('#size_header_row').html(headerHtml);
                        $('#size_qty_row').html(qtyHtml);
                        $('#size_table_container').show();

                        // Show notice for new sizes
                        var newSizeCount = response.sizes.filter(size => !currentQuantities.hasOwnProperty(size.id) && Object.keys(currentQuantities).length > 0).length;
                        if (newSizeCount > 0) {
                            var noticeHtml = '<div class="alert alert-info mt-2" role="alert">' +
                                '<i class="fas fa-info-circle me-2"></i>' +
                                '<strong>Notice:</strong> ' + newSizeCount + ' new size(s) have been added to this vendor. They are highlighted in green.' +
                                '</div>';

                            // Remove existing notice and add new one
                            $('#size_table_container .alert').remove();
                            $('#size_table_container').append(noticeHtml);
                        }
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

        // Function to get existing quantity for a size
        function getExistingQuantity(sizeId) {
            var existingInput = $('input[name="qty_' + sizeId + '"]');
            return existingInput.length > 0 ? existingInput.val() : '0';
        }

        function initializeValidation() {
            // Form validation
            $("#JobOrderForm").validate({
                ignore: [],
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
                    var jobOrderId = $('input[name="job_order_id"]').val();

                    $.ajax({
                        url: "{{ route('job_order_update', '') }}/" + jobOrderId,
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
                            var errorMessage = 'An error occurred while updating the form';
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

        // Add validation for type field if vendor is 1 on page load
        var initialVendorId = $('#vendor_id').val();
        if (initialVendorId == '1') {
            $('#type').rules('add', {
                required: true,
                messages: {
                    required: "Please select type"
                }
            });
        }
    });
</script>
@endpush