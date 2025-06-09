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

        $('#vendor_id').on('change', function() {
            var vendorId = $(this).val();
            var poSelect = $('#po_id');

            poSelect.prop('disabled', true).html('<option value="">Select PO</option>');
            $('#po_details').hide();

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

            $.ajax({
                url: '{{ route("get_config_po_details") }}',
                method: 'POST',
                data: {
                    id: poId,
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

        $(document).on('submit', '#packingConfigForm', function(e) {
            e.preventDefault();

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
                                    window.location.reload();
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
            });
        });
    });
</script>
@endpush