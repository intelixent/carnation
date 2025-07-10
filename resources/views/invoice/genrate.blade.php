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
                        Genrate Invoice
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

        $('#vendor_id').on('change', function() {
            var vendorId = $(this).val();
            var poSelect = $('#po_id');

            poSelect.prop('disabled', true).html('<option value="">Select PO</option>');
            $('#po_details').hide();

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

        // Generate Invoice button click
        $(document).on('click', '#generateInvoiceBtn', function() {
            let selectedIds = $('.po_pack:checked')
                .map((_, cb) => $(cb).val().trim())
                .get();

            // grab invoice fields
            let invoiceNo = $('#invoice_no').val().trim();
            let invoiceDate = $('#invoice_date').val();

            if (!invoiceNo || !invoiceDate) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Oops!',
                    text: 'Please enter both Invoice No. and Invoice Date'
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
                        _token: $('meta[name="csrf-token"]').attr('content')
                    })
                    .done(response => {
                        Swal.fire({
                            icon: response.success ? 'success' : 'error',
                            title: response.success ? 'Saved!' : 'Error',
                            text: response.message,
                            timer: response.success ? 2000 : null
                        }).then(() => {
                            if (response.success) window.location.reload();
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