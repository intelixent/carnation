@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
<style>
    .card .card-header {
        background-color: #fdb71473 !important;
    }

    #extractionResults {
        margin-top: 20px;
        display: none;
    }

    #resultsTable {
        width: 100%;
        border-collapse: collapse;
    }

    #resultsTable th,
    #resultsTable td {
        border: 1px solid #ddd;
        padding: 8px;
    }

    #resultsTable th {
        background-color: #f2f2f2;
        text-align: left;
    }

    .error {
        color: red;
    }

    /* ------------------------------------------------------------------
       Aditiya PO Items table - editable inputs were shrinking down to
       near-zero width inside the bordered table cells (form-control-sm
       has no min-width of its own), making Material Code truncate and
       Qty render as an empty box. Give each input room + keep the row
       from wrapping so table-responsive handles horizontal overflow
       instead of the browser crushing the inputs.
       ------------------------------------------------------------------ */
    #aditiyaItemsBody td {
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
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-dark mb-0">Pdf Extract Add</h5>
                </div>
                <div class="card-body">
                    <form id="pdfExtractAddForm" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <label for="vendor_id" class="form-label">Select Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->name ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="custom_field_no" id="custom_field_no" value="">
                            <input type="hidden" name="extraction_no" id="extraction_no" value="">
                            <div class="col-sm-6">
                                <label for="pdf_file" class="form-label">Pdf File</label>
                                <input class="form-control" type="file" id="pdf_file" name="pdf_file">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-12">
                                <button class="btn btn-primary float-end" type="submit" id="submit_btn" name="submit_btn">Submit</button>
                            </div>
                        </div>
                    </form>

                    <!-- Results section -->
                    <div class="resultsContainer">

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

        $('.select2').select2();

        $('#vendor_id').on('change', function() {
            var vendor_id = $(this).val();

            if (vendor_id) {
                $.ajax({
                    url: "{{ route('get_vendor_custom_field') }}",
                    method: 'POST',
                    data: {
                        'vendor_id': vendor_id,
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#custom_field_no').val(response.custom_field_no);
                            $('#extraction_no').val(response.extraction_no);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching vendor custom field:', error);
                    }
                });
            } else {
                $('#custom_field_no').val('');
                $('#extraction_no').val('');
            }
        });

        $('#pdfExtractAddForm').validate({
            rules: {
                extraction_no: 'required',
                pdf_file: {
                    required: true,
                    extension: "pdf"
                }
            },
            messages: {
                extraction_no: {
                    required: "Please select a Vendor"
                },
                pdf_file: {
                    required: "Please select a PDF file",
                    extension: "Only PDF files are allowed"
                }
            },
            errorClass: 'error',
            errorElement: 'span',
            errorPlacement: function(error, element) {
                if (element.hasClass("select2-hidden-accessible")) {
                    error.insertAfter(element.next('.select2-container'));
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2-container').addClass('error-border');
                } else {
                    $(element).addClass('error-border');
                }
            },
            unhighlight: function(element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2-container').removeClass('error-border');
                } else {
                    $(element).removeClass('error-border');
                }
            },
            submitHandler: function(form) {
                // Convert PDF to base64
                const pdfFile = $('#pdf_file')[0].files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    const base64Pdf = e.target.result.split(',')[1];
                    const extractionNo = $('#extraction_no').val();
                    const vendorId = $('#vendor_id').val();

                    Swal.fire({
                        title: 'Loading...',
                        html: 'Extracting the pdf details',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        // url: "http://localhost:8000/process",
                        url: '{{ route("pdf_process") }}',
                        type: "POST",
                        data: {
                            'extraction_no': extractionNo,
                            'pdf_base64': base64Pdf,
                            'vendor_id': vendorId // needed for the D-Mart size-chart lookup
                        },
                        //contentType: "application/json",
                        dataType: "json",
                        success: function(response) {
                            Swal.close();

                            if (response.status == true) {
                                $('.resultsContainer').html(response.html);

                                // If the D-Mart carton/size table was rendered, seed it with one
                                // empty color row so the user has somewhere to start typing.
                                if ($('#cartonQtyTable').length) {
                                    cartonAddColorRow();
                                }

                                // If the Aditiya PO Items table was rendered, build the initial
                                // Store-Loc summary and Qty-vs-Total validation from the extracted data.
                                if ($('#aditiyaItemsBody').length) {
                                    aditiyaRecalcSummary();
                                }

                                $.toast({
                                    heading: 'Success',
                                    text: 'PDF extracted successfully',
                                    position: 'top-center',
                                    bgColor: '#000',
                                    textColor: 'white',
                                    hideAfter: 3000,
                                    stack: 6
                                });
                            } else {
                                $.toast({
                                    heading: 'Error',
                                    text: response.message || 'Failed to extract PDF data',
                                    position: 'top-center',
                                    bgColor: '#FF0000',
                                    textColor: 'white',
                                    hideAfter: 3000,
                                    stack: 6
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.close();
                            $.toast({
                                heading: 'Error',
                                text: 'An error occurred while processing the PDF.',
                                position: 'top-center',
                                bgColor: '#FF0000',
                                textColor: 'white',
                                hideAfter: 3000,
                                stack: 6
                            });
                        }
                    });
                };

                reader.readAsDataURL(pdfFile);
                return false;
            }
        });
    });

    // ------------------------------------------------------------------
    // Verify checkbox toggles Save button - delegated because #verifyCheck
    // only exists once a vendor's response partial has been injected.
    // For Aditiya, whether the button ends up enabled also depends on the
    // Qty-vs-Total-Quantity check, so that view routes through
    // aditiyaUpdateSaveButtonState() instead of the plain toggle.
    // ------------------------------------------------------------------
    $(document).on('change', '#verifyCheck', function() {
        if ($('#aditiyaItemsBody').length) {
            aditiyaUpdateSaveButtonState();
        } else {
            $('#saveButton').prop('disabled', !this.checked);
        }
    });

    // ------------------------------------------------------------------
    // D-Mart Carton Qty / Size Breakdown table.
    // dmart_response_view.blade.php is loaded into .resultsContainer via AJAX and contains
    // no <script> of its own - all the wiring for its "Add Color" table lives here, bound
    // through event delegation on document since the table only exists after injection.
    // ------------------------------------------------------------------

    function cartonGetSizes() {
        try {
            return JSON.parse($('#cartonQtyTable').attr('data-sizes') || '[]');
        } catch (e) {
            return [];
        }
    }

    // Total Qty (pieces) extracted from the PDF at the PO level - used to derive
    // Total Cartons = Total Qty / Case Lot, the same way data-sizes drives the columns.
    function cartonGetTotalQty() {
        var raw = $('#cartonQtyTable').attr('data-total-qty') || '0';
        return parseFloat(String(raw).replace(/,/g, '')) || 0;
    }

    function cartonRecalcRowTotal($row) {
        var total = 0;
        $row.find('.carton-qty-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $row.find('.row-total').text(total);
    }

    function cartonRecalcAll() {
        var $tbody = $('#cartonQtyBody');
        if (!$tbody.length) return;

        var sizesList = cartonGetSizes();
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

        $('.total-size-cell').each(function() {
            var size = $(this).data('size');
            $(this).text(sizeTotals[size] || 0);
        });

        $('#grandTotalQty').text(grandTotal);
        $('#colorCountDisplay').val(colorCount);

        var caseLot = parseFloat($('#caseLotInput').val()) || 0;
        var ratio = colorCount > 0 ? (caseLot / colorCount) : 0;
        $('#ratioDisplay').val(ratio ? ratio.toFixed(2) : 0);

        // Total Cartons = Total Qty (from PDF) ÷ Case Lot
        var totalQtyFromPdf = cartonGetTotalQty();
        var totalCartons = caseLot > 0 ? (totalQtyFromPdf / caseLot) : 0;
        $('#totalCartonsDisplay').val(totalCartons ? totalCartons.toFixed(2) : 0);

        cartonSerialize(ratio, totalCartons);
    }

    function cartonSerialize(ratio, totalCartons) {
        var $tbody = $('#cartonQtyBody');
        var caseLot = parseFloat($('#caseLotInput').val()) || 0;
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

        $('.carton_qty_sizes').val(JSON.stringify(payload));
    }

    function cartonAddColorRow() {
        var $template = $('#cartonRowTemplate');
        if (!$template.length) return;

        var $clone = $template.clone();
        $clone.removeAttr('id');
        $('#cartonQtyBody').append($clone);
        cartonRecalcAll();
    }

    $(document).on('click', '#addColorRowBtn', function() {
        cartonAddColorRow();
    });

    $(document).on('input', '.carton-qty-input, .carton-color-input, #caseLotInput', function() {
        var $row = $(this).closest('#cartonQtyBody tr');
        if ($row.length) cartonRecalcRowTotal($row);
        cartonRecalcAll();
    });

    $(document).on('click', '.remove-color-row', function() {
        $(this).closest('tr').remove();
        cartonRecalcAll();
    });

    // ------------------------------------------------------------------
    // Aditiya (vendor 7) - editable Material Code / Qty / Store Loc on the
    // PO Items table, the Store-Loc-wise PO Summary underneath it, and a
    // Qty-vs-Total-Quantity guard so the sum of every item's Qty can never
    // exceed the Total Quantity extracted from the PDF.
    // aditiya_response_view.blade.php is loaded into .resultsContainer via AJAX
    // and contains no <script> of its own - wiring lives here, through event
    // delegation since the table only exists after injection.
    // ------------------------------------------------------------------

    // Tracks whether the current Qty total is over the PDF's Total Quantity.
    // Read by aditiyaUpdateSaveButtonState() to keep Save disabled while true.
    window.aditiyaQtyExceeded = false;

    function aditiyaGetPoData() {
        try {
            return JSON.parse($('.po_data').val() || '{}');
        } catch (e) {
            return {};
        }
    }

    function aditiyaSetPoData(data) {
        $('.po_data').val(JSON.stringify(data));
    }

    function aditiyaGetTotalQty() {
        var raw = $('#aditiyaTotalQty').val() || '0';
        return parseFloat(String(raw).replace(/,/g, '')) || 0;
    }

    function aditiyaUpdateSaveButtonState() {
        var verified = $('#verifyCheck').is(':checked');
        $('#saveButton').prop('disabled', !verified || window.aditiyaQtyExceeded === true);
    }

    function aditiyaRecalcSummary() {
        var $body = $('#aditiyaItemsBody');
        if (!$body.length) return;

        var poData = aditiyaGetPoData();
        var items = poData.po_items || [];

        // Push the current input values back into po_data so the saved
        // payload reflects whatever the user has edited.
        $body.find('tr').each(function() {
            var idx = $(this).data('index');
            if (idx === undefined || !items[idx]) return;

            items[idx]['Material Code'] = $(this).find('.aditiya-material-input').val();
            items[idx]['Qty'] = $(this).find('.aditiya-qty-input').val();
            items[idx]['Stor e Loc'] = $(this).find('.aditiya-storeloc-input').val();
        });

        poData.po_items = items;
        aditiyaSetPoData(poData);

        // ---- Group by Store Loc -> Size -> summed Qty, and total everything ----
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

        // ---- Qty-vs-Total-Quantity validation ----
        var totalFromPdf = aditiyaGetTotalQty();
        var $alert = $('#aditiyaQtyAlert');

        if (totalFromPdf > 0 && totalEntered > totalFromPdf) {
            window.aditiyaQtyExceeded = true;
            $alert.removeClass('d-none alert-success').addClass('alert-danger')
                .text('Total entered Qty (' + totalEntered + ') exceeds the PDF Total Quantity (' +
                    totalFromPdf + '). Please correct the Qty values before saving.');
            $('.aditiya-qty-input').addClass('is-invalid');
        } else if (totalFromPdf > 0) {
            window.aditiyaQtyExceeded = false;
            $alert.removeClass('d-none alert-danger').addClass('alert-success')
                .text('Total entered Qty (' + totalEntered + ') is within the PDF Total Quantity (' +
                    totalFromPdf + ').');
            $('.aditiya-qty-input').removeClass('is-invalid');
        } else {
            // No Total Quantity could be extracted from the PDF - nothing to validate against.
            window.aditiyaQtyExceeded = false;
            $alert.addClass('d-none');
            $('.aditiya-qty-input').removeClass('is-invalid');
        }

        aditiyaUpdateSaveButtonState();

        // ---- Render the Store Loc summary tables ----
        var $container = $('#aditiyaSummaryContainer');
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
        aditiyaRecalcSummary();
    });

    $(document).on('click', '#saveButton', function() {
        var vendor_name = $("#vendor_name").val();
        var custom_field_no = $("#custom_field_no").val();
        var hsn_code = $("#hsn_code").val();
        var vendor_id = $('#vendor_id').val();

        if (custom_field_no === "1" && (!hsn_code || hsn_code.trim() === "")) {
            Swal.fire({
                icon: 'warning',
                title: 'HSN Code Required',
                text: 'Please enter HSN Code before proceeding.',
                confirmButtonText: 'OK'
            });
            return false;
        }

        // Make sure the latest edits (Aditiya Material Code / Qty / Store Loc) are
        // flushed into .po_data and re-validated before we read anything below.
        if ($('#aditiyaItemsBody').length) {
            aditiyaRecalcSummary();

            if (window.aditiyaQtyExceeded) {
                Swal.fire({
                    icon: 'error',
                    title: 'Qty Exceeds Total Quantity',
                    text: 'The sum of all item Qty values cannot be more than the PDF\'s Total Quantity. Please fix the Qty values before saving.',
                    confirmButtonText: 'OK'
                });
                return false;
            }
        }

        var po_data = $(".po_data").val();
        var po_details = $(".po_details").val();
        var article_details = $(".article_details").val();
        var po_items = $(".po_items").val();
        var po_unit_price = $(".po_unit_price").val();
        var po_qty = $(".po_qty").val();
        var carton_qty_sizes = $(".carton_qty_sizes").val();

        $.ajax({
            url: "{{route('check_po_exists')}}",
            method: 'POST',
            data: {
                'po_data': po_data,
                'po_details': po_details,
                'vendor_id': vendor_id,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.exists) {
                    if (response.amended) {
                        // PO exists with status = 1 (amended) - Cannot save
                        Swal.fire({
                            icon: 'error',
                            title: 'Cannot Save PO',
                            text: `Purchase Order "${response.po_num}" already exists with amended status. Cannot create duplicate PO.`,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // PO exists with status = 0 (not amended) - Show confirmation
                        Swal.fire({
                            title: 'PO Already Exists!',
                            text: `Purchase Order "${response.po_num}" already exists in the database but is not amended. Do you want to proceed anyway?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, Save Anyway!',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                proceedWithSave();
                            }
                        });
                    }
                } else {
                    // PO doesn't exist - proceed normally
                    proceedWithSave();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking PO existence:', error);
                proceedWithSave();
            }
        });

        function proceedWithSave() {
            // Get the PDF file
            var pdfFile = $('#pdf_file')[0].files[0];

            // Create FormData object to handle file upload
            var formData = new FormData();
            if (po_data && po_data.trim() !== "" && po_data !== "undefined") {
                formData.append('po_data', po_data);
            }
            formData.append('po_details', po_details);
            formData.append('article_details', article_details);
            formData.append('po_items', po_items);
            formData.append('po_unit_price', po_unit_price);
            formData.append('po_qty', po_qty);
            formData.append('vendor_name', vendor_name);
            formData.append('vendor_id', vendor_id);
            formData.append('hsn_code', hsn_code);

            if (carton_qty_sizes && carton_qty_sizes.trim() !== "") {
                formData.append('carton_qty_sizes', carton_qty_sizes);
            }

            formData.append('_token', '{{ csrf_token() }}');

            // Append PDF file if exists
            if (pdfFile) {
                formData.append('pdf_file', pdfFile);
            }

            Swal.fire({
                title: 'Loading...',
                html: 'Please wait while we store the details',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{route('pdf_extract_store')}}",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.close();

                    if (response.success) {
                        $.toast({
                            heading: 'Success',
                            text: response.message,
                            position: 'top-center',
                            bgColor: '#000',
                            textColor: 'white',
                            hideAfter: 3000,
                            stack: 6
                        });
                        setTimeout(function() {
                            window.location.href = "{{ route('pdf_extract_all_master') }}";
                        }, 3000);
                    } else {
                        $.toast({
                            heading: 'Error',
                            text: response.message,
                            position: 'top-center',
                            bgColor: '#FF0000',
                            textColor: 'white',
                            hideAfter: 3000,
                            stack: 6
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to save details. Please try again.'
                    });
                    console.error(error);
                }
            });
        }
    });
</script>
@endpush