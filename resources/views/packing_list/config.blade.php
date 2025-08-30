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

        // Function to initialize position and per carton qty functionality
        function initializePositionAndCartonQty() {
            // Auto-increment positions when page loads (now common for all colors)
            var positionCounter = 1;
            $('.position-input').each(function() {
                if ($(this).val() == '' || $(this).val() == '0') {
                    $(this).val(positionCounter++);
                }
            });

            // Initialize total per carton qty calculation
            updateTotalPerCartonQty();
        }

        // Function to update total per carton qty for each size (simplified for common values)
        function updateTotalPerCartonQty() {
            $('.total-per-carton-qty').each(function() {
                var size = $(this).data('size');
                
                // Since it's now common for all colors, just get the single input value for this size
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
                        
                        // Initialize position and per carton qty functionality
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

        // Handle per carton qty input changes (delegated event)
        $(document).on('input', '.per-carton-qty-input', function() {
            // Validate input value
            var value = parseInt($(this).val()) || 0;
            if (value < 0) {
                $(this).val(0);
                value = 0;
            }
            
            updateTotalPerCartonQty();
        });

        // Handle position input changes (delegated event)
        $(document).on('input', '.position-input', function() {
            var value = parseInt($(this).val()) || 1;
            if (value < 1) {
                $(this).val(1);
            }
        });

        // Handle form submission
        $(document).on('submit', '#packingConfigForm', function(e) {
            e.preventDefault();

            // Validate required fields
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

            // Validate position inputs for vendors 1, 5, 6
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

            // Validate per carton qty inputs for vendors 1, 5, 6
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

            // Check if any position or per carton qty values are set for vendors 1, 5, 6
            var hasPositionData = $('.position-input').length > 0;
            var hasCartonQtyData = $('.per-carton-qty-input').length > 0;
            
            if (hasPositionData || hasCartonQtyData) {
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
                
                var summaryText = 'Configuration Summary:\n';
                if (positionSummary.length > 0) {
                    summaryText += 'Positions - ' + positionSummary.join(', ') + '\n';
                }
                if (cartonQtySummary.length > 0) {
                    summaryText += 'Per Carton Qty - ' + cartonQtySummary.join(', ');
                }
                
                Swal.fire({
                    title: 'Save Configuration?',
                    text: summaryText + '\n\nAre you sure you want to save this packing list configuration?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Save it!',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitConfiguration();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Save Configuration?',
                    text: 'Are you sure you want to save this packing list configuration?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Save it!',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitConfiguration();
                    }
                });
            }
        });

        // Function to handle the actual form submission
        function submitConfiguration() {
            Swal.fire({
                title: 'Saving Configuration...',
                text: 'Please wait while we save your packing list configuration.',
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Configuration saved successfully!',
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

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error saving configuration: ' + errorMessage,
                        confirmButtonText: 'Try Again',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }

        // Reset position counter for new data
        $(document).on('click', '.reset-positions', function() {
            Swal.fire({
                title: 'Reset Positions?',
                text: 'This will reset all position values to sequential order (1, 2, 3, ...).',
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
                        text: 'All positions have been reset to sequential order.',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                }
            });
        });

        // Clear all per carton quantities
        $(document).on('click', '.clear-carton-qty', function() {
            Swal.fire({
                title: 'Clear All Per Carton Quantities?',
                text: 'This will set all per carton quantity values to 0.',
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
                        text: 'All per carton quantities have been set to 0.',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                }
            });
        });

        // Auto-save draft functionality (optional)
        var autoSaveTimer;
        $(document).on('input', '.position-input, .per-carton-qty-input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                // Could implement auto-save draft here if needed
                console.log('Auto-save triggered (draft functionality can be added here)');
            }, 5000);
        });

        // Keyboard shortcuts
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

        // Add loading states for better UX
        $(document).on('focus', '.position-input, .per-carton-qty-input', function() {
            $(this).addClass('border-primary');
        });

        $(document).on('blur', '.position-input, .per-carton-qty-input', function() {
            $(this).removeClass('border-primary');
        });
    });
</script>
@endpush