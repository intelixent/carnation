@extends('layouts.app')
@section('pagetitle', $page_title)
@section('content')
@push('styles')
<style>
    .table thead th {
        vertical-align: middle;
        font-size: 0.875rem;
        white-space: nowrap;
    }

    .table tbody td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .table tfoot th {
        font-size: 0.875rem;
    }

    .text-end {
        text-align: right !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mt-2">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Daily Packing Report - All Vendors
                    </div>
                </div>
                <div class="card-body">
                    <form id="reportForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mt-2">
                                    <label for="date">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date" name="date" required>
                                </div>
                            </div>
                            <div class="col-md-2 mt-4">
                                <button class="btn btn-primary w-100" type="submit" id="submit_btn">
                                    <i class="fa fa-search me-1"></i> Generate
                                </button>
                            </div>
                        </div>
                    </form>

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Set max date to today
        $('#date').attr('max', new Date().toISOString().split('T')[0]);

        $('#reportForm').on('submit', function(e) {
            e.preventDefault();

            // Validate form
            if (!$('#date').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please select a date',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // Show loading alert
            Swal.fire({
                title: 'Loading...',
                text: 'Fetching data for all vendors, please wait...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Disable submit button during loading
            $('#submit_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Loading...');

            $.ajax({
                url: "{{ route('daily_packing_report_table') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    Swal.close();
                    $('#tableContainer').html(response);

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Report generated successfully for all vendors',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });

                    // Initialize export buttons after table is loaded
                    initializeExportButtons();
                },
                error: function(xhr) {
                    Swal.close();
                    console.log(xhr.responseText);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error fetching data. Please try again.',
                        confirmButtonColor: '#d33'
                    });
                },
                complete: function() {
                    $('#submit_btn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> Generate');
                }
            });
        });

        // Function to initialize export buttons
        function initializeExportButtons() {
            // Export Summary Button
            $(document).off('click', '#exportSummaryBtn').on('click', '#exportSummaryBtn', function() {
                const form = $('#exportForm');
                form.attr('action', "{{ route('daily_packing_summary_export') }}");

                Swal.fire({
                    title: 'Exporting...',
                    text: 'Generating Excel file, please wait...',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit form
                form.submit();

                // Close loading after a delay
                setTimeout(function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Excel file downloaded successfully',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }, 2000);
            });

            // Export Size Wise Button
            $(document).off('click', '#exportSizeWiseBtn').on('click', '#exportSizeWiseBtn', function() {
                const form = $('#exportForm');
                form.attr('action', "{{ route('daily_packing_sizewise_export') }}");

                Swal.fire({
                    title: 'Exporting...',
                    text: 'Generating Excel file, please wait...',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit form
                form.submit();

                // Close loading after a delay
                setTimeout(function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Excel file downloaded successfully',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }, 2000);
            });
        }
    });
</script>
@endpush