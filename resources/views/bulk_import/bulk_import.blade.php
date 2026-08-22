@extends('layouts.app')

@section('pagetitle', 'Bulk Import PO')

@section('content')
<style>
    .bulk-card-header {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
        color: #ffffff;
        border-bottom: 2px solid #3b82f6;
    }

    .file-badge {
        display: inline-block;
        background: #e2e8f0;
        color: #1e293b;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin: 4px;
        font-weight: 600;
    }

    .loader-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        color: white;
        text-align: center;
        padding-top: 20vh;
    }
</style>

<div class="loader-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
        <span class="visually-hidden">Processing...</span>
    </div>
    <h4 class="mt-3 text-white">Extracting POs, Generating Packing Lists & Invoices...</h4>
    <p class="text-light">Please wait while the PDF engine processes your files.</p>
</div>

<div class="container-fluid">
    <!-- BreadCrumbs -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Bulk Import Purchase Orders</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{ $page_main_title }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bulk Import</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- BreadCrumbs -->

    <div class="row">
        <div class="col-xl-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bulk-card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-file-import me-2 text-warning"></i>Bulk PO Upload & Document Generator
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form id="bulkPdfExtractForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-5">
                                <label for="vendor_id" class="form-label fw-bold text-dark">Select Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ $vendor->id == 1 ? 'selected' : '' }}>
                                        {{ $vendor->name }} {{ $vendor->id == 1 ? '(Jack & Jones)' : '' }}
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Vendor ID 1 Jack & Jones is optimized for automatic color-wise packing lists and tax invoices.</small>
                            </div>

                            <div class="col-md-7">
                                <label for="pdf_files" class="form-label fw-bold text-dark">Upload Multiple PO PDF Files <span class="text-danger">*</span></label>
                                <input class="form-control form-control-lg" type="file" id="pdf_files" name="pdf_files[]" multiple accept=".pdf" required>
                                <div id="selectedFilesList" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 py-2 text-white fw-bold" id="bulk_submit_btn">
                                <i class="fas fa-bolt me-2"></i> Extract & Preview All POs
                            </button>
                        </div>
                    </form>

                    <!-- Results Section -->
                    <div id="bulkResultsContainer" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
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

        if ($.fn.select2) {
            $('.select2').select2();
        }

        // File selection display
        $('#pdf_files').on('change', function(e) {
            let files = e.target.files;
            let listHtml = '';
            if (files && files.length > 0) {
                listHtml += '<strong>Selected (' + files.length + ' files):</strong> ';
                for (let i = 0; i < files.length; i++) {
                    listHtml += '<span class="file-badge"><i class="fas fa-file-pdf text-danger me-1"></i>' + files[i].name + '</span>';
                }
            }
            $('#selectedFilesList').html(listHtml);
        });

        // Form Submission
        $('#bulkPdfExtractForm').on('submit', function(e) {
            e.preventDefault();

            let filesInput = $('#pdf_files')[0];
            if (!filesInput.files || filesInput.files.length === 0) {
                Swal.fire('Warning', 'Please select at least one PDF file to upload.', 'warning');
                return false;
            }

            let formData = new FormData(this);
            $('#loadingOverlay').fadeIn(200);

            $.ajax({
                url: "{{ route('bulk_pdf_process') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#loadingOverlay').fadeOut(200);
                    if (response.status && response.html) {
                        $('#bulkResultsContainer').html(response.html).show();
                        $('html, body').animate({
                            scrollTop: $("#bulkResultsContainer").offset().top - 20
                        }, 500);
                    } else if (response.error) {
                        Swal.fire('Error', response.error, 'error');
                    }
                },
                error: function(xhr) {
                    $('#loadingOverlay').fadeOut(200);
                    let err = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to process PDFs. Please ensure Python server is running on port 8000.';
                    Swal.fire('Extraction Error', err, 'error');
                }
            });
        });
    });
</script>
@endpush
