@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
<div class="container-fluid">
    <!-- row -->

    <div class="modal fade" id="add_modal"></div>

    <div class="row mt-2">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Packing List Config
                    </div>
                </div>
                <div class="card-body">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vendor_id" class="form-label">Vendor</label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="po_id" class="form-label">PO</label>
                                <select name="po_id" id="po_id" class="form-control select2" required disabled>
                                    <option value="">Select PO</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-xl-12">
                            <div id="tableContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.select2').select2({
            width: '100%'
        });

        // Function to initialize position and per carton qty functionality for ALL vendors
        function initializePositionAndCartonQty() {
            // Auto-increment positions when page loads (now common for all colors and ALL vendors)
            var positionCounter = 1;
            $('.position-input').each(function() {
                if ($(this).val() == '' || $(this).val() == '0') {
                    $(this).val(positionCounter++);
                }
            });

            // Initialize total per carton qty calculation
            updateTotalPerCartonQty();
        }

        // Function to update total per carton qty for each size (for ALL vendors)
        function updateTotalPerCartonQty() {
            $('.total-per-carton-qty').each(function() {
                var size = $(this).data('size');

                // Get the single input value for this size
                var inputValue = $('input[name="per_carton_qtys[' + size + ']"]').val() || 0;
                var total = parseInt(inputValue);

                $(this).text(total > 0 ? total : '-');
            });
        }

        $('#vendor_id').on('change', function() {
            var vendorId = $(this).val();
            var poSelect = $('#po_id');

            poSelect.prop('disabled', true).html('<option value="">Select PO</option>');
            $('#po_details').hide();
            $('#tableContainer').empty();

            if (vendorId) {
                $.ajax({
                    url: '{{ route("get_config_vendor_po") }}',
                    method: 'POST',
                    data: {
                        vendor_id: vendorId,
                        _token: $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        var options = '<option value="">Select PO</option>';
                        $.each(response, function(index, po) {
                            options += '<option value="' + po.id + '">' + po.po_job_num + ' | ' + po.vendor_name + '</option>';
                        });
                        poSelect.html(options).prop('disabled', false);
                    },
                    error: function(xhr) {
                        console.error('Error fetching POs:', xhr.responseJSON?.error || 'Unknown error');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.error || 'Failed to fetch POs'
                        });
                    }
                });
            }
        });

        $('#po_id').on('change', function() {
            var poId = $(this).val();

            if (poId) {
                // Show loading state
                $('#tableContainer').html('<div class="text-center p-4"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div><p class="mt-2">Loading PO details...</p></div>');

                $.ajax({
                    url: '{{ route("get_config_po_details") }}',
                    method: 'POST',
                    data: {
                        id: poId,
                    },
                    success: function(response) {
                        $('#tableContainer').html(response);
                        $('.select2').select2({
                            width: '100%'
                        });

                        // Initialize position and per carton qty functionality for ALL vendors
                        setTimeout(function() {
                            initializePositionAndCartonQty();
                        }, 100);
                    },
                    error: function(xhr) {
                        console.error('Error fetching PO details:', xhr.responseJSON?.error || 'Unknown error');
                        $('#tableContainer').html('<div class="alert alert-danger">Error loading PO details. Please try again.</div>');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.error || 'Failed to fetch PO details'
                        });
                    }
                });
            } else {
                $('#tableContainer').empty();
            }
        });

        // Handle per carton qty input changes (delegated event) - for ALL vendors
        $(document).on('input', '.per-carton-qty-input', function() {
            // Validate input value
            var value = parseInt($(this).val()) || 0;
            if (value < 0) {
                $(this).val(0);
                value = 0;
            }

            updateTotalPerCartonQty();
        });

        // Handle position input changes (delegated event) - for ALL vendors
        $(document).on('input', '.position-input', function() {
            var value = parseInt($(this).val()) || 1;
            if (value < 1) {
                $(this).val(1);
            }
        });

        // Handle form submission - Updated validation for ALL vendors
        // Handle form submission - Updated validation to allow position and per carton qty updates
        $(document).on('submit', '#packingConfigForm', function(e) {
            e.preventDefault();

            // Get vendor ID and packing list status from the form
            var vendorId = parseInt($(this).data('vendor-id'));
            var hasPackingListItems = $('input[name="has_packing_list_items"]').val() === '1';

            // For new configurations, validate carton selection
            if (!hasPackingListItems) {
                var cartonId = $('#carton_select').val();
                if (!cartonId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Please select a carton before saving the configuration.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ffc107'
                    });
                    $('#carton_select').focus();
                    return;
                }
            }

            // Always validate position inputs (for both new and existing configurations)
            var hasInvalidPosition = false;
            var invalidPositionField = null;
            $('.position-input').each(function() {
                var value = parseInt($(this).val()) || 0;
                if (value < 1) {
                    hasInvalidPosition = true;
                    invalidPositionField = $(this);
                    return false;
                }
            });

            if (hasInvalidPosition) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'All position values must be greater than 0.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ffc107'
                }).then(() => {
                    if (invalidPositionField) {
                        invalidPositionField.focus();
                    }
                });
                return;
            }

            // Always validate per carton qty inputs (for both new and existing configurations)
            var hasInvalidCartonQty = false;
            var invalidCartonQtyField = null;
            $('.per-carton-qty-input').each(function() {
                var value = parseInt($(this).val()) || 0;
                if (value < 0) {
                    hasInvalidCartonQty = true;
                    invalidCartonQtyField = $(this);
                    return false;
                }
            });

            if (hasInvalidCartonQty) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Per carton quantity values cannot be negative.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ffc107'
                }).then(() => {
                    if (invalidCartonQtyField) {
                        invalidCartonQtyField.focus();
                    }
                });
                return;
            }

            // Show configuration summary for both positions and per carton quantities
            var positionSummary = [];
            var cartonQtySummary = [];

            $('.position-input').each(function() {
                var size = $(this).attr('name').match(/\[([^\]]+)\]$/)[1];
                var value = $(this).val();
                positionSummary.push(size + ': ' + value);
            });

            $('.per-carton-qty-input').each(function() {
                var size = $(this).attr('name').match(/\[([^\]]+)\]$/)[1];
                var value = $(this).val();
                if (value > 0) {
                    cartonQtySummary.push(size + ': ' + value);
                }
            });

            var actionText = hasPackingListItems ? 'Update' : 'Save';
            var confirmText = hasPackingListItems ? 'Yes, Update!' : 'Yes, Save it!';

            var summaryText = actionText + ' Configuration for Vendor ' + vendorId + ':\n';
            if (positionSummary.length > 0) {
                summaryText += 'Positions - ' + positionSummary.join(', ') + '\n';
            }
            if (cartonQtySummary.length > 0) {
                summaryText += 'Per Carton Qty - ' + cartonQtySummary.join(', ');
            }

            Swal.fire({
                title: actionText + ' Configuration?',
                text: summaryText + '\n\nAre you sure you want to ' + actionText.toLowerCase() + ' this packing list configuration?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitConfiguration();
                }
            });
        });

        // Function to handle the actual form submission
        function submitConfiguration() {
            var hasPackingListItems = $('input[name="has_packing_list_items"]').val() === '1';
            var actionText = hasPackingListItems ? 'Updating' : 'Saving';

            Swal.fire({
                title: actionText + ' Configuration...',
                text: 'Please wait while we ' + actionText.toLowerCase() + ' your packing list configuration.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("save_config_po_details") }}',
                method: 'POST',
                data: $('#packingConfigForm').serialize(),
                success: function(response) {
                    if (response.success) {
                        var hasPackingListItems = $('input[name="has_packing_list_items"]').val() === '1';
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Configuration ' + (hasPackingListItems ? 'updated' : 'saved') + ' successfully!',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#28a745',
                            timer: 3000,
                            timerProgressBar: true
                        }).then((result) => {
                            // Refresh the PO details to show updated data
                            $('#po_id').trigger('change');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Something went wrong!',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An unexpected error occurred.';

                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        // Validation errors
                        var errors = xhr.responseJSON.errors;
                        errorMessage = 'Validation failed:\n';
                        Object.keys(errors).forEach(function(key) {
                            errorMessage += '- ' + errors[key][0] + '\n';
                        });
                    }

                    var hasPackingListItems = $('input[name="has_packing_list_items"]').val() === '1';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error ' + (hasPackingListItems ? 'updating' : 'saving') + ' configuration: ' + errorMessage,
                        confirmButtonText: 'Try Again',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }

        // Reset position counter - now works for both new and existing configurations
        $(document).on('click', '.reset-positions', function() {
            var vendorId = $('#packingConfigForm').data('vendor-id');

            Swal.fire({
                title: 'Reset Positions?',
                text: 'This will reset all position values for Vendor ' + vendorId + ' to sequential order (1, 2, 3, ...).',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Reset!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    var counter = 1;
                    $('.position-input').each(function() {
                        $(this).val(counter++);
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Positions Reset!',
                        text: 'All positions have been reset to sequential order for Vendor ' + vendorId + '.',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                }
            });
        });

        // Clear all per carton quantities - now works for both new and existing configurations
        $(document).on('click', '.clear-carton-qty', function() {
            var vendorId = $('#packingConfigForm').data('vendor-id');

            Swal.fire({
                title: 'Clear All Per Carton Quantities?',
                text: 'This will set all per carton quantity values for Vendor ' + vendorId + ' to 0.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Clear!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('.per-carton-qty-input').val(0);
                    updateTotalPerCartonQty();

                    Swal.fire({
                        icon: 'success',
                        title: 'Quantities Cleared!',
                        text: 'All per carton quantities have been set to 0 for Vendor ' + vendorId + '.',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                }
            });
        });

        // Auto-save draft functionality (optional) - for ALL vendors
        var autoSaveTimer;
        $(document).on('input', '.position-input, .per-carton-qty-input', function() {
            clearTimeout(autoSaveTimer);
            var vendorId = $('#packingConfigForm').data('vendor-id');
            autoSaveTimer = setTimeout(function() {
                // Could implement auto-save draft here if needed
                console.log('Auto-save triggered for Vendor ' + vendorId + ' (draft functionality can be added here)');
            }, 5000);
        });

        // Keyboard shortcuts - for ALL vendors
        $(document).on('keydown', function(e) {
            // Ctrl+S to save
            if (e.ctrlKey && e.which == 83) {
                e.preventDefault();
                if ($('#packingConfigForm').length > 0) {
                    $('#packingConfigForm').submit();
                }
            }

            // Esc to clear focus
            if (e.which == 27) {
                $('.position-input, .per-carton-qty-input').blur();
            }
        });

        // Add loading states for better UX - for ALL vendors
        $(document).on('focus', '.position-input, .per-carton-qty-input', function() {
            $(this).addClass('border-primary');
        });

        $(document).on('blur', '.position-input, .per-carton-qty-input', function() {
            $(this).removeClass('border-primary');
        });

        // Add vendor-specific styling based on vendor ID
        $(document).on('change', '#po_id', function() {
            setTimeout(function() {
                var vendorId = $('#packingConfigForm').data('vendor-id');
                if (vendorId) {
                    // Add vendor-specific CSS classes or styling
                    $('#tableContainer').removeClass('vendor-1 vendor-3 vendor-4 vendor-5 vendor-6');
                    $('#tableContainer').addClass('vendor-' + vendorId);

                    // Optional: Add vendor-specific tooltips
                    $('.position-input').attr('title', 'Position for Vendor ' + vendorId);
                    $('.per-carton-qty-input').attr('title', 'Per Carton Quantity for Vendor ' + vendorId);
                }
            }, 200);
        });
    });
</script>
@endpush