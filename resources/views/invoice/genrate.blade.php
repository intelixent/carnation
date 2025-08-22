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
                        Generate Invoice
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

                    <!-- Invoice Preview Section -->
                    <div class="row mt-4" id="invoicePreviewSection" style="display: none;">
                        <div class="col-xl-12">
                            <h4 class="text-success mb-3">
                                Invoice Preview
                            </h4>
                            <div id="invoicePreviewContainer"></div>
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

        $('#vendor_id').on('change', function() {
            var vendorId = $(this).val();
            var poSelect = $('#po_id');

            poSelect.prop('disabled', true).html('<option value="">Select PO</option>');
            $('#po_details').hide();
            $('#invoicePreviewSection').hide();

            if (vendorId) {
                $.ajax({
                    url: '{{ route("get_complete_vendor_packing_list") }}',
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
            var vendor_id = $("#vendor_id").val();
            $('#invoicePreviewSection').hide();

            $.ajax({
                url: '{{ route("get_packging_list") }}',
                method: 'POST',
                data: {
                    id: poId,
                    vendor_id: vendor_id
                },
                success: function(response) {
                    $('#tableContainer').html(response);
                    $('.select2').select2();
                },
                error: function(xhr) {
                    console.error('Error fetching PO details:', xhr.responseJSON?.error || 'Unknown error');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.error || 'Failed to fetch PO details'
                    });
                }
            });
        });

        $(document).on('change', '.po_pack', function() {
            let selectedCount = $('.po_pack:checked').length;

            if (selectedCount > 0) {
                $('#generateBtnContainer').show();
            } else {
                $('#generateBtnContainer').hide();
            }
        });

        function displayInvoicePreview(invoiceData) {
            let previewHtml = `
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Invoice No:</strong> ${invoiceData.invoice.ref_no}
                    </div>
                    <div class="col-md-4">
                        <strong>Invoice Date:</strong> ${invoiceData.invoice.inv_date}
                    </div>
                    <div class="col-md-4">
                        <strong>GST Rate:</strong> ${invoiceData.invoice.gst_rate}%
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>SI No</th>
                                <th>Description</th>
                                <th>HSN</th>
                                <th>Style</th>
                                <th>Colors</th>
                                <th>Size</th>
                                <th>UOM</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Amount</th>
                                <th>Discount</th>
                                <th>Taxable Value</th>
                                <th>GST Rate</th>
                                <th>GST Amount</th>
                            </tr>
                        </thead>
                        <tbody>`;

            let totalAmount = 0;
            let totalDiscount = 0;
            let totalTaxable = 0;
            let totalGstAmount = 0;
            let totalQty = 0;

            invoiceData.items.forEach((item, index) => {
                totalAmount += parseFloat(item.amount);
                totalDiscount += parseFloat(item.discount);
                totalTaxable += parseFloat(item.taxable_value);
                totalGstAmount += parseFloat(item.gst_amount);
                totalQty += parseInt(item.qty);

                previewHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.description}, ${item.size}</td>
                        <td>${item.hsn_code}</td>
                        <td>${item.style}</td>
                        <td>${item.colors}</td>
                        <td>${item.size}</td>
                        <td>${item.unit}</td>
                        <td class="text-right">${item.qty}</td>
                        <td class="text-right">₹${parseFloat(item.rate).toFixed(2)}</td>
                        <td class="text-right">₹${parseFloat(item.amount).toFixed(2)}</td>
                        <td class="text-right">₹${parseFloat(item.discount).toFixed(2)}</td>
                        <td class="text-right">₹${parseFloat(item.taxable_value).toFixed(2)}</td>
                        <td class="text-center">${parseFloat(item.gst_rate).toFixed(2)}%</td>
                        <td class="text-right">₹${parseFloat(item.gst_amount).toFixed(2)}</td>
                    </tr>`;
            });

            let finalAmount = totalTaxable + totalGstAmount;

            previewHtml += `
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="7" class="text-right"><strong>Total</strong></td>
                                <td class="text-right"><strong>${totalQty}</strong></td>
                                <td></td>
                                <td class="text-right"><strong>₹${totalAmount.toFixed(2)}</strong></td>
                                <td class="text-right"><strong>₹${totalDiscount.toFixed(2)}</strong></td>
                                <td class="text-right"><strong>₹${totalTaxable.toFixed(2)}</strong></td>
                                <td></td>
                                <td class="text-right"><strong>₹${totalGstAmount.toFixed(2)}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="13" class="text-right"><strong>Final Amount</strong></td>
                                <td class="text-right"><strong>₹${finalAmount.toFixed(2)}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>`;

            $('#invoicePreviewContainer').html(previewHtml);
            $('#invoicePreviewSection').show();
        }

        // Generate Invoice button click
        $(document).on('click', '#generateInvoiceBtn', function() {
            let selectedIds = $('.po_pack:checked')
                .map((_, cb) => $(cb).val().trim())
                .get();

            // grab invoice fields
            let invoiceNo = $('#invoice_no').val().trim();
            let invoiceDate = $('#invoice_date').val();
            let gstRate = $('#gst_rate').val().trim();

            if (!invoiceNo || !invoiceDate || !gstRate) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Oops!',
                    text: 'Please enter Invoice No., Invoice Date, and GST Rate'
                });
            }

            if (isNaN(gstRate) || parseFloat(gstRate) < 0 || parseFloat(gstRate) > 100) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Invalid GST Rate',
                    text: 'GST rate must be a number between 0 and 100'
                });
            }

            Swal.fire({
                title: 'Generate Invoice?',
                text: 'This will apply to all selected lists.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Generate',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Generating…',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                $.post('{{ route("store_invoice") }}', {
                        selectedpackids: selectedIds,
                        selected_po: $('.selected_po').val(),
                        selected_vendor_id: $('.selected_vendor_id').val(),
                        invoice_no: invoiceNo,
                        invoice_date: invoiceDate,
                        gst: parseFloat(gstRate),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    })
                    .done(response => {
                        Swal.fire({
                            icon: response.success ? 'success' : 'error',
                            title: response.success ? 'Saved!' : 'Error',
                            text: response.message,
                            timer: response.success ? 2000 : null
                        }).then(() => {
                            if (response.success && response.invoice_data) {
                                displayInvoicePreview(response.invoice_data);
                            }
                        });
                    })
                    .fail(xhr => {
                        let msg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Unexpected error';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                    });
            });
        });
    });
</script>
@endpush