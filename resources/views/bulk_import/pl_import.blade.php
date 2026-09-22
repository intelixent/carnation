@extends('layouts.app')

@section('pagetitle', 'Bulk PL Import')

@section('content')
<style>
    .pl-import-card-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%) !important;
        color: #ffffff;
        border-bottom: 3px solid #3b82f6;
    }

    .pl-main-tab .nav-link {
        font-weight: 700;
        font-size: 0.92rem;
        padding: 10px 18px;
        border-radius: 8px 8px 0 0;
        background-color: #f1f5f9;
        color: #334155;
        margin-right: 4px;
        margin-bottom: 4px;
        border: 1px solid #cbd5e1;
        transition: all 0.2s ease;
    }
    .pl-main-tab .nav-link.active {
        background-color: #1e3a8a !important;
        color: #ffffff !important;
        border-color: #1e3a8a !important;
        box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.3);
    }
    .pl-main-tab .nav-link.active i {
        color: #ffffff !important;
    }

    .file-chip {
        display: inline-flex;
        align-items: center;
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #bfdbfe;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin: 4px;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .loader-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(5px);
        z-index: 9999;
        color: white;
        text-align: center;
        padding-top: 25vh;
    }

    .sticky-save-bar {
        position: sticky;
        bottom: 0;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        padding: 16px 24px;
        border-top: 2px solid #e2e8f0;
        box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
        z-index: 1040;
        border-radius: 8px;
    }
</style>

<!-- Loader Overlay -->
<div class="loader-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem; color: #3b82f6;" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h4 class="mt-3 text-white fw-bold" id="loaderTitle">Parsing Packing List Excel...</h4>
    <p class="text-light" id="loaderSubTitle">Please wait while the system validates carton ranges, colors, and sizes.</p>
</div>

<div class="container-fluid">
    <!-- BreadCrumbs -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Bulk Packing List Import</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{ $page_main_title }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">PL Import</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Main Card -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-header pl-import-card-header d-flex justify-content-between align-items-center" style="border-radius: 12px 12px 0 0;">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-boxes-packing me-2 text-warning"></i>Bulk PL Upload
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form id="bulkPlImportForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="vendor_id" class="form-label fw-bold text-dark">
                                    <i class="fas fa-building text-primary me-1"></i> Select Vendor <span class="text-danger">*</span>
                                </label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                    <option value="">-- Choose Vendor --</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ $vendor->id == 1 ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label for="excel_file" class="form-label fw-bold text-dark">
                                    <i class="fas fa-file-excel text-success me-1"></i> Upload PL Excel File <span class="text-danger">*</span>
                                </label>
                                <input class="form-control form-control-lg" type="file" id="excel_file" name="excel_file" accept=".xlsx, .xls, .csv" required>
                                <div id="selectedFileList" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <button type="button" class="btn btn-outline-secondary px-4 py-2" id="clear_file_btn" style="display: none;">
                                <i class="fas fa-times me-2"></i> Clear Selected File
                            </button>
                            <button type="submit" class="btn btn-primary px-5 py-2 text-white fw-bold ms-auto" id="pl_submit_btn" style="background-color: #1e3a8a;">
                                <i class="fas fa-magnifying-glass me-2"></i> Extract Packing Lists
                            </button>
                        </div>
                    </form>

                    <!-- Parsed Packing Lists Container -->
                    <div id="plResultsContainer" class="mt-4" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    let parsedPackingListData = [];

    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({
                placeholder: "-- Choose Vendor --",
                allowClear: false,
                width: '100%'
            });
        }

        const fileInput = document.getElementById('excel_file');

        $('#excel_file').on('change', function() {
            if (this.files.length > 0) {
                let file = this.files[0];
                let sizeKb = (file.size / 1024).toFixed(1);
                $('#selectedFileList').html(`
                    <div class="file-chip">
                        <i class="fas fa-file-excel text-success me-2 fs-15"></i>
                        <span>${file.name} (${sizeKb} KB)</span>
                    </div>
                `);
                $('#clear_file_btn').show();
            } else {
                $('#selectedFileList').html('');
                $('#clear_file_btn').hide();
            }
        });

        $('#clear_file_btn').on('click', function() {
            fileInput.value = '';
            $('#selectedFileList').html('');
            $(this).hide();
            $('#plResultsContainer').slideUp().html('');
            parsedPackingListData = [];
        });

        // Submit form for parsing
        $('#bulkPlImportForm').on('submit', function(e) {
            e.preventDefault();

            let vendorId = $('#vendor_id').val();
            if (!vendorId) {
                Swal.fire('Vendor Required', 'Please select a vendor before uploading the Excel file.', 'warning');
                return;
            }

            if (!fileInput.files || fileInput.files.length === 0) {
                Swal.fire('File Required', 'Please select an Excel file to upload.', 'warning');
                return;
            }

            let formData = new FormData(this);

            $('#loadingOverlay').fadeIn(200);
            $('#loaderTitle').text('Parsing Packing List Excel...');
            $('#loaderSubTitle').text('Validating format, carton ranges, dimensions, and PO records. Please wait...');

            $.ajax({
                url: "{{ route('bulk_pl_import_process') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#loadingOverlay').fadeOut(200);

                    if (response.status && response.html) {
                        parsedPackingListData = response.pos || [];
                        $('#plResultsContainer').html(response.html).slideDown();

                        if (response.summary) {
                            toastr.success(`Extracted ${response.summary.total_pls} color packing lists with ${response.summary.total_cartons} cartons across ${response.summary.total_pos} PO(s).`, 'Validation Successful');
                        }
                    } else {
                        Swal.fire('Validation Failed', response.error || 'Could not parse packing list data from the Excel file.', 'error');
                    }
                },
                error: function(xhr) {
                    $('#loadingOverlay').fadeOut(200);
                    let err = xhr.responseJSON ? xhr.responseJSON.error : 'An error occurred while parsing the Excel file. Please ensure columns match the required template.';
                    Swal.fire('Error', err, 'error');
                }
            });
        });

        // Discard Button
        $(document).on('click', '#cancel_pl_import_btn', function() {
            Swal.fire({
                title: 'Discard Parsed Packing Lists?',
                text: 'Are you sure you want to clear the previewed packing lists?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Discard All'
            }).then((result) => {
                if (result.isConfirmed) {
                    parsedPackingListData = [];
                    $('#plResultsContainer').slideUp().html('');
                    fileInput.value = '';
                    $('#selectedFileList').html('');
                    $('#clear_file_btn').hide();
                    toastr.info('Previewed packing lists discarded.');
                }
            });
        });

        // Save All Packing Lists Button
        $(document).on('click', '#save_all_pls_btn', function() {
            if (!parsedPackingListData || parsedPackingListData.length === 0) {
                Swal.fire('No Data', 'Please upload and parse an Excel file first.', 'warning');
                return;
            }

            let vendorId = $('#vendor_id').val();
            let validPos = parsedPackingListData.filter(p => p.po_exists && !p.is_amended && !p.has_existing_packing_list);
            let amendedPos = parsedPackingListData.filter(p => p.po_exists && p.is_amended);
            let existingPlPos = parsedPackingListData.filter(p => p.po_exists && !p.is_amended && p.has_existing_packing_list);
            let missingPos = parsedPackingListData.filter(p => !p.po_exists);

            if (validPos.length === 0) {
                let reasonHtml = '';
                if (amendedPos.length > 0 && existingPlPos.length === 0 && missingPos.length === 0) {
                    reasonHtml = 'All PO(s) in this Excel file are already amended. Skipped from re-processing.';
                } else if (existingPlPos.length > 0 && missingPos.length === 0 && amendedPos.length === 0) {
                    reasonHtml = 'All PO(s) in this Excel file already have Packing Lists created. Re-uploading is not permitted.';
                } else if (missingPos.length > 0 && existingPlPos.length === 0 && amendedPos.length === 0) {
                    reasonHtml = 'None of the PO(s) in this Excel file exist in the database. Please bulk upload the POs first.';
                } else {
                    reasonHtml = 'None of the PO(s) in this Excel file can be saved (they are either already amended, missing, or already have packing lists).';
                }

                Swal.fire({
                    title: 'Cannot Save Packing Lists',
                    html: `<div class="text-start">
                        <p class="text-danger fw-bold"><i class="fas fa-circle-xmark me-1"></i> ${reasonHtml}</p>
                    </div>`,
                    icon: 'error'
                });
                return;
            }

            let confirmHtml = `<div class="text-start">
                <p>You are about to amend <strong>${validPos.length} PO(s)</strong> with Job Order numbers and generate packing lists.</p>`;

            if (amendedPos.length > 0) {
                let amendedList = amendedPos.map(p => `PO #${p.po_num}`).join(', ');
                confirmHtml += `<div class="alert alert-warning p-2 fs-12 mb-2">
                    <i class="fas fa-ban me-1 text-warning"></i>
                    <strong>Skipping ${amendedPos.length} already amended PO(s):</strong> ${amendedList}<br>
                    <small>These POs have already been amended and will be skipped.</small>
                </div>`;
            }

            if (existingPlPos.length > 0) {
                let existingList = existingPlPos.map(p => `PO #${p.po_num}`).join(', ');
                confirmHtml += `<div class="alert alert-warning p-2 fs-12 mb-2">
                    <i class="fas fa-lock me-1 text-warning"></i>
                    <strong>Skipping ${existingPlPos.length} PO(s) with existing Packing Lists:</strong> ${existingList}<br>
                    <small>Packing lists are already created for these POs and cannot be re-uploaded.</small>
                </div>`;
            }

            if (missingPos.length > 0) {
                let missingList = missingPos.map(p => `PO #${p.po_num}`).join(', ');
                confirmHtml += `<div class="alert alert-danger p-2 fs-12 mb-0">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    <strong>Skipping ${missingPos.length} non-uploaded PO(s):</strong> ${missingList}<br>
                    <small>These POs will <u>not</u> be stored because they have not been uploaded yet.</small>
                </div>`;
            }

            confirmHtml += `</div>`;

            Swal.fire({
                title: 'Save & Generate Packing Lists?',
                html: confirmHtml,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e3a8a',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-save me-1"></i> Yes, Generate Now!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loadingOverlay').fadeIn(200);
                    $('#loaderTitle').text('Saving Packing Lists...');
                    $('#loaderSubTitle').text('Inserting Packing List Config, Master, and Carton Items for eligible POs...');

                    $.ajax({
                        url: "{{ route('bulk_pl_import_store') }}",
                        type: 'POST',
                        data: {
                            vendor_id: vendorId,
                            data: JSON.stringify(validPos),
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            $('#loadingOverlay').fadeOut(200);

                            if (response.success) {
                                let skippedHtml = '';
                                if (response.skipped && response.skipped.length > 0) {
                                    skippedHtml = `<div class="alert alert-warning text-start mt-2 p-2 fs-12">
                                        <strong>Skipped POs:</strong><br>${response.skipped.join('<br>')}
                                    </div>`;
                                }

                                Swal.fire({
                                    title: 'Packing Lists Created!',
                                    html: `<div class="text-start">
                                        <p class="text-success fw-bold mb-1"><i class="fas fa-check-circle me-1"></i> ${response.message}</p>
                                        ${skippedHtml}
                                    </div>`,
                                    icon: 'success',
                                    confirmButtonText: '<i class="fas fa-list-check me-1"></i> Go to Packing List Master',
                                    showCancelButton: true,
                                    cancelButtonText: 'Import More'
                                }).then((navResult) => {
                                    if (navResult.isConfirmed) {
                                        window.location.href = "{{ route('packing_list_master') }}";
                                    } else {
                                        parsedPackingListData = [];
                                        $('#plResultsContainer').slideUp().html('');
                                        fileInput.value = '';
                                        $('#selectedFileList').html('');
                                        $('#clear_file_btn').hide();
                                    }
                                });
                            } else {
                                Swal.fire('Save Failed', response.message || 'Failed to create packing lists.', 'error');
                            }
                        },
                        error: function(xhr) {
                            $('#loadingOverlay').fadeOut(200);
                            let msg = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred while saving packing lists.';
                            Swal.fire('Error', msg, 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush