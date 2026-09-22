@extends('layouts.app')

@section('pagetitle', 'Bulk PO Import')

@section('content')
<style>
    .po-import-card-header {
        background: linear-gradient(135deg, #0f766e 0%, #134e4a 100%) !important;
        color: #ffffff;
        border-bottom: 3px solid #14b8a6;
    }

    .po-main-tab .nav-link {
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
    .po-main-tab .nav-link.active {
        background-color: #0f766e !important;
        color: #ffffff !important;
        border-color: #0f766e !important;
        box-shadow: 0 4px 6px -1px rgba(15, 118, 110, 0.3);
    }
    .po-main-tab .nav-link.active i {
        color: #ffffff !important;
    }

    .file-chip {
        display: inline-flex;
        align-items: center;
        background: #e6fffa;
        color: #115e59;
        border: 1px solid #99f6e4;
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
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(5px);
        z-index: 9999;
        color: white;
        text-align: center;
        padding-top: 25vh;
    }

    .sticky-save-bar {
        position: sticky;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        padding: 16px 24px;
        border-top: 2px solid #e2e8f0;
        box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
        z-index: 1040;
        border-radius: 8px;
    }

    /* Aditya PO Items Styling */
    #aditiyaItemsBody td,
    [id^="aditiyaItemsBody"] td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .aditiya-material-input {
        min-width: 150px;
    }

    .aditiya-qty-input {
        min-width: 90px;
        text-align: right;
    }

    .aditiya-storeloc-input {
        min-width: 140px;
    }

    .aditiya-material-input.is-invalid,
    .aditiya-qty-input.is-invalid,
    .aditiya-storeloc-input.is-invalid {
        border-color: #dc3545;
    }
</style>

<!-- Loader Overlay -->
<div class="loader-overlay" id="loadingOverlay">
    <div class="spinner-border text-teal" style="width: 4rem; height: 4rem; color: #14b8a6;" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h4 class="mt-3 text-white fw-bold" id="loaderTitle">Extracting Purchase Orders...</h4>
    <p class="text-light" id="loaderSubTitle">Please wait while the PDF microservice processes your files.</p>
</div>

<div class="container-fluid">
    <!-- BreadCrumbs -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Bulk Purchase Order Import</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{ $page_main_title }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">PO Import</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Main Card -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-header po-import-card-header d-flex justify-content-between align-items-center" style="border-radius: 12px 12px 0 0;">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-file-invoice me-2 text-warning"></i>Bulk PO Upload
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form id="bulkPoImportForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="vendor_id" class="form-label fw-bold text-dark">
                                    <i class="fas fa-building text-teal me-1"></i> Select Vendor <span class="text-danger">*</span>
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
                                <label for="pdf_files" class="form-label fw-bold text-dark">
                                    <i class="fas fa-file-pdf text-danger me-1"></i> Upload Multiple PO PDF Files <span class="text-danger">*</span>
                                </label>
                                <input class="form-control form-control-lg" type="file" id="pdf_files" name="pdf_files[]" multiple accept=".pdf" required>
                                <div id="selectedFilesList" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <button type="button" class="btn btn-outline-secondary px-4 py-2" id="clear_files_btn" style="display: none;">
                                <i class="fas fa-times me-2"></i> Clear Selected Files
                            </button>
                            <button type="submit" class="btn btn-teal px-5 py-2 text-white fw-bold ms-auto" id="po_submit_btn" style="background-color: #0f766e;">
                                <i class="fas fa-cogs me-2"></i> Extract POs
                            </button>
                        </div>
                    </form>

                    <!-- Extracted Tabbed POs Container -->
                    <div id="poResultsContainer" class="mt-4" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
    let extractedPosData = [];

    $(document).ready(function() {
        // Initialize Select2 on Vendor dropdown
        if ($.fn.select2) {
            $('.select2').select2({
                placeholder: "-- Choose Vendor --",
                allowClear: false,
                width: '100%'
            });
        }

        const fileInput = document.getElementById('pdf_files');

        $('#pdf_files').on('change', function() {
            displaySelectedFiles();
        });

        $('#clear_files_btn').on('click', function() {
            fileInput.value = '';
            $('#selectedFilesList').html('');
            $(this).hide();
        });

        // Form Submit for Extraction
        $('#bulkPoImportForm').on('submit', function(e) {
            e.preventDefault();

            let vendorId = $('#vendor_id').val();
            if (!vendorId) {
                Swal.fire('Vendor Required', 'Please select a vendor before extracting POs.', 'warning');
                return;
            }

            let files = fileInput.files;
            if (files.length === 0) {
                Swal.fire('No Files', 'Please select at least one PDF file.', 'warning');
                return;
            }

            let formData = new FormData(this);

            $('#loadingOverlay').fadeIn(200);
            $('#loaderTitle').text('Extracting Purchase Orders...');
            $('#loaderSubTitle').text('Processing ' + files.length + ' PDF file(s). Please wait...');

            $.ajax({
                url: "{{ route('bulk_po_import_process') }}",
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
                        extractedPosData = response.pos || [];
                        $('#poResultsContainer').html(response.html).slideDown();
                        scopeAccordionIds();

                        // Initialize Vendor-Specific UI logic across all extracted tabs
                        $('#bulkPoTabsContent .tab-pane').each(function(tabIdx) {
                            var $pane = $(this);

                            // D-Mart: Seed color row and recalculate
                            if ($pane.find('#cartonQtyTable, [id^="cartonQtyTable"]').length) {
                                cartonAddColorRowToPane($pane);
                            }

                            // Aditya Birla: Build initial summary and validate
                            if ($pane.find('#aditiyaItemsBody, [id^="aditiyaItemsBody"]').length) {
                                aditiyaRecalcSummaryPane($pane, tabIdx);
                            }
                        });

                        if (response.errors && response.errors.length > 0) {
                            toastr.warning(response.errors.length + ' file(s) had extraction issues.', 'Partial Success');
                        } else {
                            toastr.success('Successfully extracted ' + extractedPosData.length + ' PO(s).', 'Extraction Complete');
                        }
                    } else {
                        Swal.fire('Extraction Failed', response.error || 'No valid PO data could be extracted from the uploaded files.', 'error');
                    }
                },
                error: function(xhr) {
                    $('#loadingOverlay').fadeOut(200);
                    let err = xhr.responseJSON ? xhr.responseJSON.error : 'An error occurred during extraction. Please make sure the Python microservice is running on port 8000.';
                    Swal.fire('Error', err, 'error');
                }
            });
        });

        // Discard button
        $(document).on('click', '#cancel_all_btn', function() {
            Swal.fire({
                title: 'Discard Extracted POs?',
                text: 'Are you sure you want to clear all extracted POs?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Discard All'
            }).then((result) => {
                if (result.isConfirmed) {
                    extractedPosData = [];
                    $('#poResultsContainer').slideUp().html('');
                    fileInput.value = '';
                    $('#selectedFilesList').html('');
                    $('#clear_files_btn').hide();
                    toastr.info('Extracted POs discarded.');
                }
            });
        });

        // Save All POs Alone button
        $(document).on('click', '#save_all_pos_btn', function() {
            if (extractedPosData.length === 0) {
                Swal.fire('No POs to Save', 'Please extract at least one PO first.', 'warning');
                return;
            }

            let vendorId = $('#vendor_id').val();

            // Validate all tabs and flush latest edits before submitting
            for (let i = 0; i < extractedPosData.length; i++) {
                let p = extractedPosData[i];
                let $pane = $('#po-pane-' + i);
                if ($pane.length) {
                    // Aditya validation
                    if ($pane.find('#aditiyaItemsBody, [id^="aditiyaItemsBody"]').length) {
                        aditiyaRecalcSummaryPane($pane, i);
                        if (p._qtyExceeded) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Qty Exceeds Total Quantity',
                                text: `In PO "${p.po_num}", the sum of item quantities exceeds the PDF Total Quantity. Please fix the Qty values before saving.`,
                                confirmButtonText: 'OK'
                            });
                            return;
                        }
                    }

                    // D-Mart serialization
                    if ($pane.find('#cartonQtyTable, [id^="cartonQtyTable"]').length) {
                        cartonRecalcPane($pane);
                    }
                }
            }

            let amendedCount = extractedPosData.filter(p => p.duplicate_status === 'amended').length;

            let confirmText = `You are about to store ${extractedPosData.length} Purchase Order(s) alone (Status = Unamended).`;
            if (amendedCount > 0) {
                confirmText += ` Note: ${amendedCount} PO(s) already exist with Amended status and will be skipped to protect existing workflows.`;
            }

            Swal.fire({
                title: 'Save POs Alone?',
                text: confirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0f766e',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-save me-1"></i> Yes, Save Now!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loadingOverlay').fadeIn(200);
                    $('#loaderTitle').text('Saving Purchase Orders Alone...');
                    $('#loaderSubTitle').text('Storing PO master and item records into database...');

                    $.ajax({
                        url: "{{ route('bulk_po_import_store') }}",
                        type: 'POST',
                        data: {
                            vendor_id: vendorId,
                            pos_data: JSON.stringify(extractedPosData),
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            $('#loadingOverlay').fadeOut(200);

                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    html: `<div class="text-start">
                                            <p class="fw-bold text-success">${response.message}</p>
                                            ${response.saved && response.saved.length ? `<p class="mb-1"><strong>Saved POs:</strong> ${response.saved.join(', ')}</p>` : ''}
                                            ${response.skipped && response.skipped.length ? `<p class="mb-1 text-danger"><strong>Skipped:</strong> ${response.skipped.join(', ')}</p>` : ''}
                                           </div>`,
                                    icon: 'success',
                                    confirmButtonText: 'View PO Master',
                                    showCancelButton: true,
                                    cancelButtonText: 'Import More'
                                }).then((navResult) => {
                                    if (navResult.isConfirmed) {
                                        window.location.href = "{{ route('pdf_extract_all_master') }}";
                                    } else {
                                        extractedPosData = [];
                                        $('#poResultsContainer').slideUp().html('');
                                        fileInput.value = '';
                                        $('#selectedFilesList').html('');
                                        $('#clear_files_btn').hide();
                                    }
                                });
                            } else {
                                Swal.fire('Save Failed', response.message || 'Could not save purchase orders.', 'error');
                            }
                        },
                        error: function(xhr) {
                            $('#loadingOverlay').fadeOut(200);
                            let err = xhr.responseJSON ? xhr.responseJSON.message : 'Server error while saving POs.';
                            Swal.fire('Error', err, 'error');
                        }
                    });
                }
            });
        });
    });

    function displaySelectedFiles() {
        let files = document.getElementById('pdf_files').files;
        let listContainer = $('#selectedFilesList');
        listContainer.html('');

        if (files.length > 0) {
            $('#clear_files_btn').show();
            let chipsHtml = `<div class="d-flex flex-wrap align-items-center mt-2">
                <span class="badge bg-teal me-2 p-2 text-white" style="background-color: #0f766e;"><i class="fas fa-file-pdf me-1"></i> ${files.length} File(s) Selected</span>`;
            
            for (let i = 0; i < files.length; i++) {
                let sizeKb = (files[i].size / 1024).toFixed(1);
                chipsHtml += `<span class="file-chip">
                    <i class="fas fa-paperclip me-1 text-teal" style="color: #0f766e;"></i> ${files[i].name} (${sizeKb} KB)
                </span>`;
            }
            chipsHtml += `</div>`;
            listContainer.html(chipsHtml);
        } else {
            $('#clear_files_btn').hide();
        }
    }

    function scopeAccordionIds() {
        $('#bulkPoTabsContent .tab-pane').each(function(tabIdx) {
            var $pane = $(this);
            $pane.find('.accordion').each(function(accIdx) {
                var uniqueAccId = 'acc_tab_' + tabIdx + '_' + accIdx;
                $(this).attr('id', uniqueAccId);

                $(this).find('.accordion-item').each(function(itemIdx) {
                    var $item = $(this);
                    var uniqueHeadingId = 'heading_tab_' + tabIdx + '_' + accIdx + '_' + itemIdx;
                    var uniqueCollapseId = 'collapse_tab_' + tabIdx + '_' + accIdx + '_' + itemIdx;

                    $item.find('.accordion-header').attr('id', uniqueHeadingId);
                    $item.find('.accordion-button').attr('data-bs-target', '#' + uniqueCollapseId).attr('aria-controls', uniqueCollapseId);
                    $item.find('.accordion-collapse').attr('id', uniqueCollapseId).attr('aria-labelledby', uniqueHeadingId).attr('data-bs-parent', '#' + uniqueAccId);
                });
            });
        });
    }

    // =========================================================================
    // D-MART (Vendor 8) Tab Scoped Functions
    // =========================================================================

    function cartonGetSizesFromPane($pane) {
        try {
            var $table = $pane.find('#cartonQtyTable, [id^="cartonQtyTable"]');
            return JSON.parse($table.attr('data-sizes') || '[]');
        } catch (e) {
            return [];
        }
    }

    function cartonGetTotalQtyFromPane($pane) {
        var $table = $pane.find('#cartonQtyTable, [id^="cartonQtyTable"]');
        var raw = $table.attr('data-total-qty') || '0';
        return parseFloat(String(raw).replace(/,/g, '')) || 0;
    }

    function cartonRecalcRowTotal($row) {
        var total = 0;
        $row.find('.carton-qty-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $row.find('.row-total').text(total);
    }

    function cartonRecalcPane($pane) {
        var $tbody = $pane.find('#cartonQtyBody, [id^="cartonQtyBody"]');
        if (!$tbody.length) return;

        var tabIdx = $pane.index();
        var sizesList = cartonGetSizesFromPane($pane);
        var sizeTotals = {};
        sizesList.forEach(function(size) {
            sizeTotals[size] = 0;
        });

        var grandTotal = 0;
        var colorCount = 0;

        $tbody.find('tr').each(function() {
            var $row = $(this);
            var colorVal = $row.find('.carton-color-input').val().trim();
            if (colorVal !== '') colorCount++;

            $row.find('.carton-qty-input').each(function() {
                var size = $(this).data('size');
                var val = parseFloat($(this).val()) || 0;
                sizeTotals[size] = (sizeTotals[size] || 0) + val;
                grandTotal += val;
            });
        });

        $pane.find('.total-size-cell').each(function() {
            var size = $(this).data('size');
            $(this).text(sizeTotals[size] || 0);
        });

        $pane.find('#grandTotalQty, [id^="grandTotalQty"]').text(grandTotal);
        $pane.find('#colorCountDisplay, [id^="colorCountDisplay"]').val(colorCount);

        var caseLot = parseFloat($pane.find('#caseLotInput, [id^="caseLotInput"]').val()) || 0;
        var ratio = colorCount > 0 ? (caseLot / colorCount) : 0;
        $pane.find('#ratioDisplay, [id^="ratioDisplay"]').val(ratio ? ratio.toFixed(2) : 0);

        var totalQtyFromPdf = cartonGetTotalQtyFromPane($pane);
        var totalCartons = caseLot > 0 ? (totalQtyFromPdf / caseLot) : 0;
        $pane.find('#totalCartonsDisplay, [id^="totalCartonsDisplay"]').val(totalCartons ? totalCartons.toFixed(2) : 0);

        cartonSerializePane($pane, tabIdx, ratio, totalCartons);
    }

    function cartonSerializePane($pane, tabIdx, ratio, totalCartons) {
        var $tbody = $pane.find('#cartonQtyBody, [id^="cartonQtyBody"]');
        var caseLot = parseFloat($pane.find('#caseLotInput, [id^="caseLotInput"]').val()) || 0;
        var payload = [];

        $tbody.find('tr').each(function() {
            var $row = $(this);
            var color = $row.find('.carton-color-input').val().trim();
            if (color === '') return;

            $row.find('.carton-qty-input').each(function() {
                var qty = parseInt($(this).val()) || 0;
                if (qty > 0) {
                    payload.push({
                        color: color,
                        size: $(this).data('size'),
                        qty: qty,
                        case_lot: caseLot,
                        ratio: ratio ? Number(ratio.toFixed(2)) : 0,
                        total_cartons: totalCartons ? Number(totalCartons.toFixed(2)) : 0
                    });
                }
            });
        });

        if (extractedPosData[tabIdx]) {
            extractedPosData[tabIdx].carton_qty_sizes = payload;
            extractedPosData[tabIdx].total_case_lot = caseLot;
        }
    }

    function cartonAddColorRowToPane($pane) {
        var $template = $pane.find('#cartonRowTemplate, [id^="cartonRowTemplate"]');
        if (!$template.length) return;

        var $clone = $template.clone();
        $clone.removeAttr('id');
        $pane.find('#cartonQtyBody, [id^="cartonQtyBody"]').append($clone);
        cartonRecalcPane($pane);
    }

    $(document).on('click', '#addColorRowBtn, [id^="addColorRowBtn"]', function() {
        var $pane = $(this).closest('.tab-pane');
        cartonAddColorRowToPane($pane);
    });

    $(document).on('input', '.carton-qty-input, .carton-color-input, #caseLotInput, [id^="caseLotInput"]', function() {
        var $pane = $(this).closest('.tab-pane');
        var $row = $(this).closest('tr');
        if ($row.length) cartonRecalcRowTotal($row);
        cartonRecalcPane($pane);
    });

    $(document).on('click', '.remove-color-row', function() {
        var $pane = $(this).closest('.tab-pane');
        $(this).closest('tr').remove();
        cartonRecalcPane($pane);
    });

    // =========================================================================
    // ADITYA BIRLA (Vendor 7) Tab Scoped Functions
    // =========================================================================

    function aditiyaGetTotalQtyFromPane($pane) {
        var raw = $pane.find('#aditiyaTotalQty, [id^="aditiyaTotalQty"]').val() || '0';
        return parseFloat(String(raw).replace(/,/g, '')) || 0;
    }

    function aditiyaRecalcSummaryPane($pane, tabIdx) {
        var $body = $pane.find('#aditiyaItemsBody, [id^="aditiyaItemsBody"]');
        if (!$body.length) return;

        if (tabIdx === undefined || tabIdx === null || tabIdx < 0) {
            tabIdx = $pane.index();
        }

        var poEntry = extractedPosData[tabIdx] || {};
        var items = poEntry.po_items || (poEntry.po_details ? poEntry.po_details.po_items : []) || [];

        // Push current input values into items array
        $body.find('tr').each(function() {
            var idx = $(this).data('index');
            if (idx === undefined || !items[idx]) return;

            items[idx]['Material Code'] = $(this).find('.aditiya-material-input').val();
            items[idx]['Qty'] = $(this).find('.aditiya-qty-input').val();
            items[idx]['Stor e Loc'] = $(this).find('.aditiya-storeloc-input').val();
        });

        poEntry.po_items = items;
        if (poEntry.po_details) {
            poEntry.po_details.po_items = items;
        }

        // Group by Store Loc -> Size -> summed Qty
        var groups = {};
        var totalEntered = 0;

        items.forEach(function(item) {
            var storeLoc = ((item['Stor e Loc'] || '').trim()) || 'Unassigned';
            var size = ((item['Size'] || '').trim()) || 'N/A';
            var qty = parseFloat(String(item['Qty']).replace(/,/g, '')) || 0;

            if (!groups[storeLoc]) groups[storeLoc] = {};
            groups[storeLoc][size] = (groups[storeLoc][size] || 0) + qty;
            totalEntered += qty;
        });

        poEntry.total_qty = totalEntered;

        // Qty-vs-Total-Quantity validation
        var totalFromPdf = aditiyaGetTotalQtyFromPane($pane);
        var $alert = $pane.find('#aditiyaQtyAlert, [id^="aditiyaQtyAlert"]');

        if (totalFromPdf > 0 && totalEntered > totalFromPdf) {
            poEntry._qtyExceeded = true;
            $alert.removeClass('d-none alert-success').addClass('alert-danger')
                .text('Total entered Qty (' + totalEntered + ') exceeds the PDF Total Quantity (' +
                    totalFromPdf + '). Please correct the Qty values before saving.');
            $pane.find('.aditiya-qty-input').addClass('is-invalid');
        } else if (totalFromPdf > 0) {
            poEntry._qtyExceeded = false;
            $alert.removeClass('d-none alert-danger').addClass('alert-success')
                .text('Total entered Qty (' + totalEntered + ') is within the PDF Total Quantity (' +
                    totalFromPdf + ').');
            $pane.find('.aditiya-qty-input').removeClass('is-invalid');
        } else {
            poEntry._qtyExceeded = false;
            $alert.addClass('d-none');
            $pane.find('.aditiya-qty-input').removeClass('is-invalid');
        }

        // Render Store Loc summary tables
        var $container = $pane.find('#aditiyaSummaryContainer, [id^="aditiyaSummaryContainer"]');
        $container.empty();

        var storeLocs = Object.keys(groups).sort();
        if (storeLocs.length === 0) {
            $container.html('<p class="text-muted">No items to summarize.</p>');
            return;
        }

        storeLocs.forEach(function(storeLoc) {
            var sizes = Object.keys(groups[storeLoc]).sort();
            var grandTotal = 0;

            var $section = $('<div class="mb-4"></div>');
            $section.append('<h6>Store Loc: ' + $('<div>').text(storeLoc).html() + '</h6>');

            var $tableWrap = $('<div class="table-responsive"></div>');
            var $table = $('<table class="table table-bordered table-sm mb-0"></table>');

            var $thead = $('<thead class="table-dark"><tr><th>Size</th></tr></thead>');
            var $headRow = $thead.find('tr');
            sizes.forEach(function(size) {
                $headRow.append($('<th></th>').text(size));
            });
            $headRow.append('<th>Total</th>');

            var $tbody = $('<tbody><tr><td>Qty</td></tr></tbody>');
            var $bodyRow = $tbody.find('tr');
            sizes.forEach(function(size) {
                var qty = groups[storeLoc][size];
                grandTotal += qty;
                $bodyRow.append($('<td></td>').text(qty));
            });
            $bodyRow.append($('<td></td>').html('<strong>' + grandTotal + '</strong>'));

            $table.append($thead).append($tbody);
            $tableWrap.append($table);
            $section.append($tableWrap);
            $container.append($section);
        });
    }

    $(document).on('input', '.aditiya-material-input, .aditiya-qty-input, .aditiya-storeloc-input', function() {
        var $pane = $(this).closest('.tab-pane');
        aditiyaRecalcSummaryPane($pane, $pane.index());
    });
</script>
@endpush
