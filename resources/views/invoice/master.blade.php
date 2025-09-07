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
@endpush

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

    <div class="modal fade" id="detail_modal"></div>
    <div class="modal fade" id="invoice_modal"></div>
    <div class="modal fade" id="grn_modal"></div>

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Invoice Master
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100 dataTable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Ref no</th>
                                    <th>Date</th>
                                    <th>PO Number</th>
                                    <th>Vendor Name</th>
                                    <th>Created at</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($InvoiceMaster as $key => $invoice)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ $invoice->ref_no }}
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                <li><a class="dropdown-item view_invoice" data-id="{{ $invoice->id }}" href="javascript:void(0);">View</a></li>
                                                <li><a class="dropdown-item update_invoice" data-id="{{ $invoice->id }}" href="javascript:void(0);">Update Invoice Details</a></li>
                                                <li><a class="dropdown-item update_grn" data-id="{{ $invoice->id }}" href="javascript:void(0);">Update GRN Details</a></li>
                                                <li><a class="dropdown-item" target="_blank" href="{{route('generateInvoice',['id' => $invoice->id])}}">Print</a></li>
                                                <li><a class="dropdown-item delete_invoice" data-id="{{ $invoice->id }}" href="javascript:void(0);">Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>{{ $invoice->inv_date }}</td>
                                    <td>{{ $invoice->po->po_num }}</td>
                                    <td>{{ $invoice->po->vendor->name ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($invoice->created_at)->format('d-m-Y h:i A'); }}</td>
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

        $(document).on('click', '.view-pl', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('packing_list_details')}}",
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

        $(document).on('click', '.delete-pl', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action will permanently delete the packing list!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('packing_list_delete') }}",
                        method: 'POST',
                        data: {
                            id: id,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Packing list has been deleted.',
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
                                'Could not delete the packing list. Please try again later.',
                                'error'
                            );
                        }
                    });
                }
            });
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

            $("#InvoiceUpdateForm").validate({
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
                        url: "{{ route('invoice_details_update') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
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