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

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        E-invoice Master
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
<script type="text/javascript">
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
                text: 'Fetching E-Invoice data, please wait...',
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

            $.ajax({
                url: "{{ route('e_invoice_master_table') }}",
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

        $(document).on('click', '#downloadExcelData', function(e) {
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
                html: 'Your E-Invoice Excel report is being generated. Please wait...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Create download URL
            const downloadUrl = "{{ route('e_invoice_excel_download') }}" + `?from_date=${from_date}&to_date=${to_date}`;

            // Create iframe for download (better than direct link)
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = downloadUrl;
            document.body.appendChild(iframe);

            // Clean up iframe after download
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 5000);

            // Close loading after a delay
            setTimeout(() => {
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Download Started',
                    text: 'Your E-invoice Excel report download has started!',
                    confirmButtonText: 'OK',
                    timer: 3000
                });
            }, 2000);
        });
    });
</script>
@endpush