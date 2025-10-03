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

    #bulkUpdateContainer {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        display: none;
    }

    .status-badge {
        font-size: 0.875em;
        padding: 0.5em 0.75em;
    }

    .progress-circle {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: bold;
        margin: 3px auto;
        border: 2px solid;
        transition: all 0.3s ease;
    }

    /* Transport details completed - green with tick */
    .progress-circle.transport-complete {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
    }

    .progress-circle.transport-complete::before {
        content: "✓";
    }

    /* Transport details incomplete - empty circle */
    .progress-circle.transport-incomplete {
        background-color: #fff;
        border-color: #dc3545;
    }

    .progress-container {
        text-align: center;
        padding: 3px;
    }

    /* DataTable styling - smaller padding and font size */
    #invoiceDataTable {
        font-size: 12px;
    }

    #invoiceDataTable th,
    #invoiceDataTable td {
        padding: 8px 6px !important;
        vertical-align: middle;
    }

    #invoiceDataTable thead th {
        font-size: 11px;
        font-weight: 600;
        text-align: center;
    }

    #invoiceDataTable tbody td {
        font-size: 11px;
    }

    /* Dropdown button styling for smaller table */
    .dropdown .btn {
        font-size: 10px;
        padding: 4px 8px;
    }

    /* Status badge styling for smaller table */
    .status-badge {
        font-size: 0.7em !important;
        padding: 0.3em 0.5em !important;
    }

    /* Checkbox styling */
    .form-check-input {
        transform: scale(0.8);
    }

    /* Progress circle hover effect */
    .progress-circle:hover {
        transform: scale(1.2);
        cursor: help;
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
    <div class="modal fade" id="invoice_modal"></div>
    <div class="modal fade" id="grn_modal"></div>

    <div class="modal fade" id="bulkStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status for Selected Invoices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bulkStatusForm">
                        <div class="mb-3">
                            <label for="modal_bulk_status" class="form-label">Select Status</label>
                            <select id="modal_bulk_status" name="status_id" class="form-control select2-modal" required>
                                <option value="">Select Status</option>
                                @foreach($statuses as $status)
                                @if($status->id != 4)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="modalDateContainer" style="display: none;">
                            <label for="modal_bulk_date" class="form-label">Select Date</label>
                            <input type="date" class="form-control" id="modal_bulk_date" name="selected_date">
                        </div>
                        <div class="alert alert-info">
                            <small>Selected invoices: <span id="modalSelectedCount">0</span></small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmBulkUpdate" class="btn btn-success">
                        <i class="fa fa-check"></i> Update Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- row -->
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Invoice Master
                    </div>
                </div>
                <div class="card-body">
                    <form id="invoiceForm" method="POST">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="vendor_id" class="form-label">Vendor</label>
                                    <select name="vendor_id" id="vendor_id" class="form-control select2">
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status_id" class="form-label">Status</label>
                                    <select name="status_id" id="status_id" class="form-control select2">
                                        <option value="">Select Status</option>
                                        @foreach($statuses as $status)
                                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <label for="from_date">From Date</label>
                                <input type="date" class="form-control" id="from_date" name="from_date">
                            </div>

                            <div class="col-sm-2">
                                <label for="to_date">To Date</label>
                                <input type="date" class="form-control" id="to_date" name="to_date">
                            </div>

                            <div class="col-sm-2 mt3">
                                <button class="btn btn-primary float-end" type="submit" id="submit_btn" name="submit_btn">Generate</button>
                            </div>
                        </div>
                    </form>

                    <div class="row mt-4" id="tableContainer" style="display: none;">
                        <div class="col-xl-12">
                            <div class="row mt-4">
                                <div class="col-xl-12">
                                    <div id="tableContainer">
                                        <div class="table-responsive mt-2">
                                            <table class="table table-bordered text-nowrap w-100" id="invoiceDataTable">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                                            S.No
                                                        </th>
                                                        <th>Ref no</th>
                                                        <th>Date</th>
                                                        <th>PO Number</th>
                                                        <th>Vendor Name</th>
                                                        <th>Created at</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
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

        updateBulkControls();

        $('.select2').select2({
            width: '100%'
        });
        
        var dataTable = null;

        // Initialize DataTable
        function initializeDataTable() {
            if (dataTable) {
                dataTable.destroy();
            }

            dataTable = $('#invoiceDataTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('invoice_table') }}",
                    type: "POST",
                    data: function(d) {
                        d.vendor_id = $('#vendor_id').val();
                        d.status_id = $('#status_id').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                    }
                },
                columns: [{
                        data: 'checkbox_sno',
                        name: 'checkbox_sno',
                        orderable: false,
                        searchable: false,
                        width: '8%'
                    },
                    {
                        data: 'ref_no_actions',
                        name: 'ref_no_actions',
                        orderable: false,
                        width: '12%'
                    },
                    {
                        data: 'inv_date',
                        name: 'inv_date',
                        width: '10%'
                    },
                    {
                        data: 'po_number',
                        name: 'po_number',
                        width: '12%'
                    },
                    {
                        data: 'vendor_name',
                        name: 'vendor_name',
                        width: '20%'
                    },
                    {
                        data: 'formatted_date',
                        name: 'created_at',
                        width: '15%'
                    },
                    {
                        data: 'status_badge',
                        name: 'status_badge',
                        orderable: false,
                        width: '13%'
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                pageLength: 25,
                responsive: true,
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
                }
            });

            // Show the table container
            $('#tableContainer').show();
        }

        // Handle select all checkbox
        $(document).on('change', '#selectAll', function() {
            var isChecked = $(this).prop('checked');
            $('.invoice-checkbox').prop('checked', isChecked);
            updateBulkControls();
        });

        // Handle individual checkbox changes
        $(document).on('change', '.invoice-checkbox', function() {
            updateBulkControls();

            // Update select all checkbox
            var totalCheckboxes = $('.invoice-checkbox').length;
            var checkedCheckboxes = $('.invoice-checkbox:checked').length;

            $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        });


        function updateBulkControls() {
            var checkedCount = $('.invoice-checkbox:checked').length;
            $('#selectedCount').text(checkedCount);

            // Show/hide the bulk update button based on selection
            if (checkedCount > 0) {
                // Show a simple button instead of the complex container
                if ($('#bulkUpdateBtn').length === 0) {
                    // Add bulk update button after the form if it doesn't exist
                    $('#invoiceForm').after(`
                        <div class="row mt-3" id="bulkUpdateSection">
                            <div class="col-12">
                                <div class="alert alert-info d-flex justify-content-between align-items-center">
                                    <span>Selected invoices: <strong><span id="selectedCount">${checkedCount}</span></strong></span>
                                    <button type="button" id="bulkUpdateBtn" class="btn btn-success">
                                        <i class="fa fa-edit"></i> Update Status
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    $('#bulkUpdateSection').show();
                    $('#selectedCount').text(checkedCount);
                }
            } else {
                $('#bulkUpdateSection').hide();
            }
        }

        // Update the bulk update button click handler to properly initialize Select2 for modal
        $(document).on('click', '#bulkUpdateBtn', function() {
            var selectedIds = [];
            $('.invoice-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one invoice'
                });
                return;
            }

            // Update modal counter
            $('#modalSelectedCount').text(selectedIds.length);

            // Reset form
            $('#modal_bulk_status').val('').trigger('change');
            $('#modal_bulk_date').val('');
            $('#modalDateContainer').hide();

            // Show modal
            $('#bulkStatusModal').modal('show');
        });

        // Initialize Select2 for modal when modal is shown
        $('#bulkStatusModal').on('shown.bs.modal', function() {
            // Initialize Select2 with proper dropdownParent
            $('#modal_bulk_status').select2({
                width: '100%',
                dropdownParent: $('#bulkStatusModal .modal-content'),
                placeholder: 'Select Status'
            });
        });

        // Destroy Select2 when modal is hidden to prevent conflicts
        $('#bulkStatusModal').on('hidden.bs.modal', function() {
            if ($('#modal_bulk_status').hasClass('select2-hidden-accessible')) {
                $('#modal_bulk_status').select2('destroy');
            }
        });

        // Handle status change in modal (keep existing logic)
        $(document).on('change', '#modal_bulk_status', function() {
            var statusId = $(this).val();
            if (statusId == '3') { // Assuming status ID 3 requires date
                $('#modalDateContainer').show();
                $('#modal_bulk_date').prop('required', true);
            } else {
                $('#modalDateContainer').hide();
                $('#modal_bulk_date').prop('required', false);
                $('#modal_bulk_date').val('');
            }
        });

        // Handle bulk update confirmation from modal
        $(document).on('click', '#confirmBulkUpdate', function() {
            var selectedIds = [];
            $('.invoice-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            var statusId = $('#modal_bulk_status').val();
            var selectedDate = $('#modal_bulk_date').val();

            if (!statusId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Status',
                    text: 'Please select a status to update'
                });
                return;
            }

            if (statusId == '3' && !selectedDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Date',
                    text: 'Please select a date for this status'
                });
                return;
            }

            // Hide modal first
            $('#bulkStatusModal').modal('hide');

            // Confirm bulk update
            Swal.fire({
                title: 'Confirm Bulk Update',
                text: `Are you sure you want to update ${selectedIds.length} invoice(s)?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Update!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkUpdate(selectedIds, statusId, selectedDate);
                }
            });
        });

        // Remove the old bulk update container and related code, replace with this:
        // Handle cancel - uncheck all
        $(document).on('click', '#bulkUpdateSection .btn-secondary, [data-bs-dismiss="modal"]', function() {
            $('.invoice-checkbox, #selectAll').prop('checked', false);
            updateBulkControls();
        });

        // Update the existing performBulkUpdate function (keep as is, but update success callback):
        function performBulkUpdate(ids, statusId, selectedDate) {
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while we update the invoices',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: "{{ route('invoice_bulk_status_update') }}",
                method: 'POST',
                data: {
                    ids: ids,
                    status_id: statusId,
                    selected_date: selectedDate,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            // Reset selections and reload table
                            $('.invoice-checkbox, #selectAll').prop('checked', false);
                            updateBulkControls();
                            if (dataTable) {
                                dataTable.ajax.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to update invoices'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    let errorMessage = 'An error occurred while updating invoices';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage
                    });
                }
            });
        }

        // Validate date range only if both dates are filled
        $('#from_date, #to_date').on('change', function() {
            var fromDate = $('#from_date').val();
            var toDate = $('#to_date').val();

            if (fromDate && toDate && fromDate > toDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Date Range',
                    text: 'From date cannot be greater than To date',
                    confirmButtonColor: '#3085d6'
                });
                $('#to_date').val('');
            }
        });

        $('#invoiceForm').on('submit', function(e) {
            e.preventDefault();

            var fromDate = $('#from_date').val();
            var toDate = $('#to_date').val();

            if (fromDate && toDate && fromDate > toDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'From date cannot be greater than To date',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            Swal.fire({
                title: 'Loading...',
                text: 'Fetching Invoice data, please wait...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $('#submit_btn').prop('disabled', true).text('Loading...');

            try {
                initializeDataTable();

                setTimeout(() => {
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'Data Loaded Successfully!',
                        text: 'Invoice data has been loaded successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }, 1000);

            } catch (error) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Error loading data. Please try again.',
                    confirmButtonColor: '#d33'
                });
            } finally {
                $('#submit_btn').prop('disabled', false).text('Generate');
            }
        });

        $(document).on('click', '.update_invoice', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('invoice_details_edit')}}",
                method: 'POST',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#invoice_modal").html(response);
                    initValidation();
                    $("#invoice_modal").modal('show');
                }
            });
        });

        $(document).on('click', '.update_grn', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('grn_details_edit')}}",
                method: 'POST',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#grn_modal").html(response);
                    initGrnValidation();
                    $("#grn_modal").modal('show');
                }
            });
        });

        function initValidation() {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('.modal-body')
            });

            // Get current invoice ID for duplicate check exclusion
            var currentInvoiceId = $('input[name="id"]').val();

            // Check for duplicate invoice number on blur (excluding current invoice)
            $(document).on('blur', '#invoice_no', function() {
                let invoiceNo = $(this).val().trim();
                if (invoiceNo) {
                    checkDuplicateInvoiceForUpdate(invoiceNo, currentInvoiceId);
                }
            });

            function checkDuplicateInvoiceForUpdate(invoiceNo, currentId) {
                $.ajax({
                    url: '{{ route("check_duplicate_invoice") }}',
                    method: 'POST',
                    data: {
                        invoice_no: invoiceNo,
                        current_id: currentId, // Pass current invoice ID to exclude from check
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.exists) {
                            $('#invoice_no').addClass('is-invalid');
                            Swal.fire({
                                icon: 'error',
                                title: 'Duplicate Invoice Number',
                                text: 'This invoice number already exists. Please use a different number.',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            $('#invoice_no').removeClass('is-invalid');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error checking duplicate invoice:', xhr);
                    }
                });
            }

            $("#InvoiceUpdateForm").validate({
                rules: {
                    invoice_no: {
                        required: true,
                        maxlength: 100,
                        remote: {
                            url: '{{ route("check_duplicate_invoice") }}',
                            type: 'POST',
                            data: {
                                invoice_no: function() {
                                    return $('#invoice_no').val();
                                },
                                current_id: function() {
                                    return $('input[name="id"]').val(); // Exclude current invoice
                                },
                                _token: function() {
                                    return $('meta[name="csrf-token"]').attr('content');
                                }
                            },
                            dataFilter: function(response) {
                                var json = JSON.parse(response);
                                return !json.exists; // Return true if NOT exists (valid)
                            }
                        }
                    },
                    invoice_date: {
                        required: true,
                        date: true
                    },
                },
                messages: {
                    invoice_no: {
                        required: "Please enter invoice number",
                        remote: "This invoice number already exists"
                    },
                    invoice_date: {
                        required: "Please select invoice date",
                        date: "Please enter a valid date"
                    },
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
                    // Additional pre-submit duplicate check
                    var invoiceNo = $('#invoice_no').val().trim();
                    var currentId = $('input[name="id"]').val();

                    $.ajax({
                        url: '{{ route("check_duplicate_invoice") }}',
                        method: 'POST',
                        data: {
                            invoice_no: invoiceNo,
                            current_id: currentId,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.exists) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Duplicate Invoice Number',
                                    text: 'This invoice number already exists. Please use a different number.',
                                    confirmButtonText: 'OK'
                                });
                                return;
                            }

                            // Proceed with form submission
                            submitUpdateForm(form);
                        },
                        error: function(xhr) {
                            console.error('Error checking duplicate:', xhr);
                            // Proceed anyway if check fails
                            submitUpdateForm(form);
                        }
                    });
                }
            });

            function submitUpdateForm(form) {
                var formData = new FormData(form);

                Swal.fire({
                    title: 'Updating Invoice...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: "{{ route('invoice_details_update') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            $("#invoice_modal").modal('hide');
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
                        Swal.close();
                        let errorMessage = 'An error occurred while submitting the form';

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.duplicate) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors && xhr.responseJSON.errors.invoice_no) {
                                errorMessage = xhr.responseJSON.errors.invoice_no[0];
                            } else {
                                errorMessage = xhr.responseJSON.message || errorMessage;
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            }
        }

        function initGrnValidation() {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('.modal-body')
            });

            // Auto-calculate when GRN qty changes
            $(document).off('input', 'input[name="grn_qty"]');
            $(document).on('input', 'input[name="grn_qty"]', function() {
                calculateGrnValues();
            });

            // Auto-calculate debit note tax amount and total value
            $(document).off('input', 'input[name="debit_note_value"], input[name="debit_note_tax_rate"]');
            $(document).on('input', 'input[name="debit_note_value"], input[name="debit_note_tax_rate"]', function() {
                calculateDebitNoteValues();
            });

            // Handle discrepancy change
            $(document).off('change', 'input[name="discrepancy"]');
            $(document).on('change', 'input[name="discrepancy"]', function() {
                if ($(this).val() === 'yes') {
                    $('#remarks').val('shortage');
                } else {
                    $('#remarks').val('');
                }
            });

            // Initialize calculations on page load
            calculateGrnValues();
            calculateDebitNoteValues();

            $("#GrnUpdateForm").validate({
                rules: {},
                messages: {},
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
                        url: "{{ route('grn_details_update') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $("#grn_modal").modal('hide');
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
        }

        function calculateGrnValues() {
            var invoiceQty = parseFloat($('input[name="total_invoice_qty"]').val()) || 0;
            var grnQty = parseFloat($('input[name="grn_qty"]').val()) || 0;
            var unitPrice = parseFloat($('input[name="unit_price_after_discount"]').val()) || 0;
            var gstRate = parseFloat($('input[name="invoice_gst_rate"]').val()) || 0;

            // Calculate difference (Invoice Qty - GRN Qty)
            var shortageQty = invoiceQty - grnQty;
            $('input[name="short_inv_vs_grn"]').val(shortageQty.toFixed(2));

            // If there's shortage, calculate debit note value
            if (shortageQty > 0) {
                var debitNoteValue = shortageQty * unitPrice;
                $('input[name="debit_note_value"]').val(debitNoteValue.toFixed(2));

                // Set GST rate if not already set
                if (!$('input[name="debit_note_tax_rate"]').val()) {
                    $('input[name="debit_note_tax_rate"]').val(gstRate);
                }

                // Recalculate debit note values
                calculateDebitNoteValues();
            } else {
                // Reset debit note values if no shortage
                $('input[name="debit_note_value"]').val('0.00');
                $('input[name="debit_note_tax_amount"]').val('0.00');
                $('input[name="total_debit_note_value"]').val('0.00');
            }
        }

        function calculateDebitNoteValues() {
            var debitValue = parseFloat($('input[name="debit_note_value"]').val()) || 0;
            var taxRate = parseFloat($('input[name="debit_note_tax_rate"]').val()) || 0;

            var taxAmount = (debitValue * taxRate) / 100;
            var totalValue = debitValue + taxAmount;

            $('input[name="debit_note_tax_amount"]').val(taxAmount.toFixed(2));
            $('input[name="total_debit_note_value"]').val(totalValue.toFixed(2));
        }
    });
</script>
@endpush