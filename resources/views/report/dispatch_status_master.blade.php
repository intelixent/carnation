@extends('layouts.app')
@section('pagetitle', $page_title)
@section('content')

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

    #fixedbutton {
        position: fixed;
        bottom: 3px;
        /* right: 0px;  */
        z-index: 10001;
        width: 75%;
    }

    .myTable .input-group {
        display: block;
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
                    <li class="breadcrumb-item active" aria-current="page">{{$page_title}}</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- BreadCrumbs -->

    <div class="modal fade" id="update_modal"></div>

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Dispatch Status Report
                    </div>
                </div>
                <div class="card-body">
                    <form id="reportForm" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <label for="from_date">From Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="from_date" name="from_date" required>
                            </div>

                            <div class="col-sm-4">
                                <label for="to_date">To Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="to_date" name="to_date" required>
                            </div>

                            <div class="col-sm-4 mt3">
                                <button class="btn btn-primary float-end" type="submit" id="submit_btn" name="submit_btn">Generate</button>
                            </div>
                        </div>
                    </form>

                    <!-- Data Table -->
                    <div class="row mt-4">
                        <div class="col-xl-12">
                            <div id="tableContainer"></div>
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
    var selectedCheckBoxArray = [];
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.select2').select2({
            placeholder: function() {
                return $(this).data('placeholder');
            }
        });

        var today = new Date();
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        $('#from_date').val(firstDay.toISOString().split('T')[0]);
        $('#to_date').val(lastDay.toISOString().split('T')[0]);

        // Validate date range
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
                $('#to_date').val(fromDate);
            }
        });

        $('#reportForm').on('submit', function(e) {
            e.preventDefault();

            var fromDate = $('#from_date').val();
            var toDate = $('#to_date').val();

            if (fromDate > toDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'From date cannot be greater than To date',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            // Show loading alert
            Swal.fire({
                title: 'Loading...',
                text: 'Fetching data, please wait...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Disable submit button during loading
            $('#submit_btn').prop('disabled', true).text('Loading...');
            selectedCheckBoxArray = [];

            $.ajax({
                url: "{{ route('dispatch_status_report_table') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    // Close loading alert
                    Swal.close();

                    // Load table data
                    $('#tableContainer').html(response);

                    // Initialize DataTable
                    $('#DataTable').DataTable();


                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Data Loaded Successfully!',
                        text: 'E-Invoice data has been loaded successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                },
                error: function(xhr) {
                    // Close loading alert
                    Swal.close();

                    console.log(xhr.responseText);

                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error fetching data. Please try again.',
                        confirmButtonColor: '#d33'
                    });
                },
                complete: function() {
                    // Re-enable submit button
                    $('#submit_btn').prop('disabled', false).text('Generate');
                }
            });
        });

        $(document).on('click', '.downloadExcelData', function(e) {
            e.preventDefault();

            const from_date = $('#from_date').val();
            const to_date = $('#to_date').val();

            // Validation
            if (!from_date || !to_date) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select from_date, to_date before downloading.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Show SweetAlert loader
            Swal.fire({
                title: 'Preparing Download',
                html: 'Your Dispatch Excel report is being generated. Please wait...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Build query string safely
            const params = new URLSearchParams();
            params.set('from_date', from_date);
            params.set('to_date', to_date);

            const downloadUrl = `{{ route('dispatch_status_report_excel_download') }}?${params.toString()}`;

            // Create for download (better than direct link)
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = downloadUrl;
            document.body.appendChild(iframe);

            // Clean up after download
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 5000);

            // Close loading after a delay
            setTimeout(() => {
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Download Started',
                    text: 'Your Dispatch Excel report download has started!',
                    confirmButtonText: 'OK',
                    timer: 3000
                });
            }, 2000);
        });

        $(document).on('click', '.update_grn', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('dispatch_status_report_edit')}}",
                method: 'POST',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#update_modal").html(response);
                    initGrnValidation();
                    $("#update_modal").modal('show');
                }
            });
        });

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
                        url: "{{ route('dispatch_status_report_update') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $("#update_modal").modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                    timer: 3000
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