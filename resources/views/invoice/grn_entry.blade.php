@extends('layouts.app')
@section('pagetitle', $page_title)
@section('content')
@push('styles')
<style>
    .error-border {
        border: 1px solid #dc3545 !important;
    }

    .success-border {
        border: 1px solid #28a745 !important;
    }

    .error {
        color: #dc3545;
        font-size: 80%;
        margin-top: 0.25rem;
        display: block;
    }

    .discrepancy-yes {
        background-color: #f8d7da;
        color: #721c24;
    }

    .discrepancy-no {
        background-color: #d4edda;
        color: #155724;
    }

    .grn-qty-input {
        width: 100px;
    }

    .qty-comparison {
        font-size: 0.9em;
        font-weight: bold;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .btn-update-grn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .submit-section {
        background-color: #f8f9fa;
        border-top: 2px solid #dee2e6;
        margin-top: 10px;
        padding: 10px;
        border-radius: 0 0 8px 8px;
    }

    .submit-info {
        background-color: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 15px;
    }

    /* Custom SweetAlert2 Progress Bar */
    .swal2-progress-bar {
        background: #28a745 !important;
        height: 10px !important;
        border-radius: 5px !important;
    }

    .swal2-progress-steps {
        font-weight: bold;
        color: #495057;
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

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        GRN Entry
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Invoice No</th>
                                    <th>Date</th>
                                    <th>PO Number</th>
                                    <th>Vendor Name</th>
                                    <th>GRN Date</th>
                                    <th>GRN Qty</th>
                                    <th>Discrepancy</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($InvoiceMaster as $index => $invoice)
                                @php
                                $grnDetails = json_decode($invoice->grn_details, true) ?? [];
                                $invoiceQty = $invoice->invoiceData['total_invoice_qty'] ?? 0;
                                $grnQty = $grnDetails['grn_qty'] ?? '';
                                $grnDate = $grnDetails['grn_date'] ?? '';
                                $discrepancy = 'nil';
                                $discrepancyClass = 'discrepancy-no';

                                if (!empty($grnQty) && is_numeric($grnQty)) {
                                if (floatval($grnQty) != floatval($invoiceQty)) {
                                $discrepancy = 'yes';
                                $discrepancyClass = 'discrepancy-yes';
                                }
                                }
                                @endphp
                                <tr data-invoice-id="{{ $invoice->id }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $invoice->ref_no }}</td>
                                    <td>{{ date('d-m-Y', strtotime($invoice->inv_date)) }}</td>
                                    <td>{{ $invoice->po->po_no ?? 'N/A' }}</td>
                                    <td>{{ $invoice->po->vendor->name ?? 'N/A' }}</td>
                                    <td>
                                        <input type="date"
                                            class="form-control grn-date"
                                            data-invoice-id="{{ $invoice->id }}"
                                            value="{{ $grnDate }}"
                                            style="width: 150px;">
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-center">
                                            <input type="number"
                                                class="form-control grn-qty-input grn-qty"
                                                data-invoice-id="{{ $invoice->id }}"
                                                data-invoice-qty="{{ $invoiceQty }}"
                                                value="{{ $grnQty }}"
                                                min="0"
                                                step="1"
                                                placeholder="Enter GRN Qty">
                                            <small class="text-muted mt-1">Inv: {{ $invoiceQty }}</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $discrepancyClass }} discrepancy-badge"
                                            data-invoice-id="{{ $invoice->id }}">
                                            {{ ucfirst($discrepancy) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No invoices found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Submit Section -->
                    <div class="submit-section">
                        <div class="submit-info">
                            <div class="row">
                                <div class="col-md-12 text-center">
                                    <button type="button" id="btn-submit-all" class="btn btn-success btn-md">
                                        <i class="fas fa-save"></i> Submit All GRN Entries
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="validation-summary" class="alert alert-warning" style="display: none;">
                            <h6><i class="fas fa-exclamation-triangle"></i> Validation Summary:</h6>
                            <ul id="validation-list"></ul>
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

        // Function to update discrepancy based on quantities
        function updateDiscrepancy(invoiceId, grnQty, invoiceQty) {
            const discrepancyBadge = $(`.discrepancy-badge[data-invoice-id="${invoiceId}"]`);
            const grnInput = $(`.grn-qty[data-invoice-id="${invoiceId}"]`);

            if (grnQty === '' || grnQty === null) {
                discrepancyBadge.text('Nil').removeClass('discrepancy-yes discrepancy-no').addClass('discrepancy-no');
                grnInput.removeClass('error-border success-border');
                return;
            }

            const grnQuantity = parseFloat(grnQty);
            const invoiceQuantity = parseFloat(invoiceQty);

            if (grnQuantity !== invoiceQuantity) {
                discrepancyBadge.text('Yes').removeClass('discrepancy-no').addClass('discrepancy-yes');
                grnInput.removeClass('success-border').addClass('error-border');
            } else {
                discrepancyBadge.text('Nil').removeClass('discrepancy-yes').addClass('discrepancy-no');
                grnInput.removeClass('error-border').addClass('success-border');
            }
        }

        // Function to validate and count ready invoices
        function validateAndCountReady() {
            let readyCount = 0;
            let validationIssues = [];

            $('tbody tr[data-invoice-id]').each(function() {
                const invoiceId = $(this).data('invoice-id');
                const grnDate = $(`.grn-date[data-invoice-id="${invoiceId}"]`).val();
                const grnQty = $(`.grn-qty[data-invoice-id="${invoiceId}"]`).val();
                const invoiceNo = $(this).find('td:nth-child(2)').text();

                if (grnDate && grnQty && parseFloat(grnQty) > 0) {
                    readyCount++;
                } else if (grnDate || grnQty) {
                    // Partial data
                    let issue = `${invoiceNo}: `;
                    if (!grnDate) issue += 'Missing GRN Date';
                    if (!grnQty || parseFloat(grnQty) <= 0) {
                        if (!grnDate) issue += ', ';
                        issue += 'Missing/Invalid GRN Quantity';
                    }
                    validationIssues.push(issue);
                }
            });

            // Show/hide validation summary
            if (validationIssues.length > 0) {
                $('#validation-list').html(validationIssues.map(issue => `<li>${issue}</li>`).join(''));
                $('#validation-summary').show();
            } else {
                $('#validation-summary').hide();
            }

            return readyCount;
        }

        // Handle GRN quantity input change
        $(document).on('input', '.grn-qty', function() {
            const invoiceId = $(this).data('invoice-id');
            const grnQty = $(this).val();
            const invoiceQty = $(this).data('invoice-qty');

            updateDiscrepancy(invoiceId, grnQty, invoiceQty);
            validateAndCountReady();
        });

        // Handle GRN date change
        $(document).on('change', '.grn-date', function() {
            validateAndCountReady();
        });

        // Handle Submit All button click
        $('#btn-submit-all').click(function() {
            const readyCount = validateAndCountReady();

            if (readyCount === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Data Ready',
                    text: 'No invoices are ready for submission. Please fill both GRN Date and GRN Quantity for at least one invoice.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // Show confirmation dialog
            Swal.fire({
                title: 'Confirm Bulk Update',
                html: `Are you sure you want to update <strong>${readyCount}</strong> invoice(s) with GRN details?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Update All',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Collect data for ready invoices
                    let updateData = [];

                    $('tbody tr[data-invoice-id]').each(function() {
                        const invoiceId = $(this).data('invoice-id');
                        const grnDate = $(`.grn-date[data-invoice-id="${invoiceId}"]`).val();
                        const grnQty = $(`.grn-qty[data-invoice-id="${invoiceId}"]`).val();
                        const invoiceQty = $(`.grn-qty[data-invoice-id="${invoiceId}"]`).data('invoice-qty');

                        if (grnDate && grnQty && parseFloat(grnQty) > 0) {
                            const discrepancy = (parseFloat(grnQty) !== parseFloat(invoiceQty)) ? 'yes' : 'nil';
                            const shortInvVsGrn = parseFloat(invoiceQty) - parseFloat(grnQty);

                            updateData.push({
                                id: invoiceId,
                                grn_date: grnDate,
                                grn_qty: grnQty,
                                discrepancy: discrepancy,
                                short_inv_vs_grn: shortInvVsGrn.toFixed(2),
                                grn_status: 'GRN Updated',
                                reached_date: grnDate,
                                pod_date: grnDate,
                                remarks: discrepancy === 'yes' ? `Discrepancy found: Invoice Qty ${invoiceQty}, GRN Qty ${grnQty}` : null
                            });
                        }
                    });

                    // Process updates
                    processUpdates(updateData);
                }
            });
        });

        // Function to process updates with simple loading + success dialog
        function processUpdates(updateData) {
            let completed = 0;
            const total = updateData.length;

            // Simple loading modal
            Swal.fire({
                title: 'Loading...',
                html: 'Updating GRN details, please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            function processNext() {
                if (completed >= total) {
                    // All done - show success modal
                    Swal.fire({
                        icon: 'success',
                        title: 'Update Complete!',
                        text: 'All GRN details have been updated successfully.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745',
                        allowOutsideClick: false
                    }).then(() => {
                        // Reload after OK
                        location.reload();
                    });
                    return;
                }

                const data = updateData[completed];

                $.ajax({
                    url: '{{ route("grn_entry_details_update") }}',
                    type: 'POST',
                    data: data,
                    success: function() {
                        completed++;
                        setTimeout(processNext, 200);
                    },
                    error: function() {
                        completed++;
                        setTimeout(processNext, 200);
                    }
                });
            }

            // Start processing
            processNext();
        }

        // Initialize discrepancy display on page load
        $('.grn-qty').each(function() {
            const invoiceId = $(this).data('invoice-id');
            const grnQty = $(this).val();
            const invoiceQty = $(this).data('invoice-qty');

            if (grnQty) {
                updateDiscrepancy(invoiceId, grnQty, invoiceQty);
            }
        });

        // Initial validation count
        validateAndCountReady();
    });
</script>
@endpush