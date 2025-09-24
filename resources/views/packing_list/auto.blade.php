@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
<style>
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
        color: white;
    }

    .sizes-table {
        overflow: visible;
    }

    .quantity-input {
        width: 80px;
    }
</style>
<div class="container-fluid">
    <!-- row -->

    <div class="modal fade" id="add_modal"></div>

    <div class="row mt-2">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Automatic Packing List
                    </div>
                </div>
                <div class="card-body">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="po_search">Search JobNo/PO No/Vendor</label>
                                <select class="form-control select2-po-search"
                                    id="po_search"
                                    name="po_id"
                                    data-placeholder="Search by Job/PO Number / Vendor Name"
                                    required>
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Color</label>
                            <select class="form-control select2" id="colorSelect" required disabled>
                                <option value="">Select Color</option>
                            </select>
                        </div>
                    </div>

                    <!-- PO Details Display -->
                    <div class="row mt-3" id="po_details" style="display: none;">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>PO Number:</strong> <span id="po_num"></span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>Job Number:</strong> <span id="po_job_num"></span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>PO Date:</strong> <span id="po_date"></span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>Vendor:</strong> <span id="vendor_name"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table Section -->
                    <div class="row mt-4" id="items_section" style="display: none;">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Packing Lists</h5>
                                <button type="button" class="btn btn-primary" id="printBtn">
                                    <i class="fas fa-print"></i> Print Packing List
                                </button>
                            </div>
                            <div id="packing_lists_container">
                                <!-- Packing lists will be loaded here -->
                            </div>
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
        // Variables to store current selection
        var currentPoId = null;
        var currentColor = null;

        // helper to toggle PO-search enabled state
        function togglePOSearch(enabled) {
            $('#po_search').prop('disabled', !enabled);
            // redraw Select2 so it reflects the disabled state
            $('#po_search').trigger('change.select2');
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize Select2 functions
        function initializeSelect2() {
            $('.select2').select2();

            // Initialize PO Search Select2
            $('.select2-po-search').select2({
                placeholder: "Search by PO Number or Vendor Name",
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route("packing_list_search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.po_job_num + ' || ' + item.vendor_name
                                };
                            })
                        };
                    },
                    cache: true
                }
            });
        }

        // Function to reinitialize PO search Select2
        function reinitializePOSearch() {
            $('#po_search').select2('destroy');
            initializeSelect2();
        }

        // Initial initialization
        initializeSelect2();

        $('#po_search').on('change', function() {
            var poId = $(this).val();

            // Reset stored values
            currentPoId = null;
            currentColor = null;

            if (poId) {
                // Reset dependent fields
                $('#colorSelect').prop('disabled', true).html('<option value="">Select Color</option>');
                $('.add-item').prop('disabled', true);
                $('#items_section').hide();
                $('#print_section').hide();
                $('#itemsTable tbody').empty();

                $.ajax({
                    url: '{{ route("get_packing_po_details") }}',
                    type: 'GET',
                    data: {
                        id: poId
                    },
                    success: function(data) {
                        $('#po_num').text(data.po_num);
                        $('#po_job_num').text(data.po_job_num);
                        $('#po_date').text(data.po_date_formatted);
                        $('#vendor_name').text(data.vendor_name);

                        if (!$('#vendor_id').length) {
                            $('body').append('<input type="hidden" id="vendor_id" value="' + data.vendor_id + '">');
                        } else {
                            $('#vendor_id').val(data.vendor_id);
                        }

                        $('#po_details').show();
                        loadColors(poId);
                    },
                    error: function(xhr) {
                        console.error('Error fetching PO details');
                        $('#po_details, #items_section, #print_section').hide();
                    }
                });
            } else {
                $('#po_details, #items_section, #print_section').hide();
                $('#itemsTable tbody').empty();
                $('#colorSelect').prop('disabled', true).html('<option value="">Select Color</option>');
                $('.add-item').prop('disabled', true);
            }
        });

        // Load colors for selected PO
        function loadColors(poId) {
            $.ajax({
                url: '{{ route("get_po_colors") }}',
                type: 'GET',
                data: {
                    po_id: poId
                },
                success: function(colors) {
                    var options = '<option value="">Select Color</option>';
                    colors.forEach(function(color) {
                        options += '<option value="' + color + '">' + color + '</option>';
                    });
                    $('#colorSelect').html(options).prop('disabled', false);
                }
            });
        }

        // Handle color selection - Load table and reinitialize PO search
        $('#colorSelect').on('change', function() {
            var color = $(this).val();
            var poId = $('#po_search').val();

            // Store current values
            currentPoId = poId;
            currentColor = color;

            if (color && poId) {
                $('.add-item').prop('disabled', false);
                $('#items_section').show();
                $('#print_section').show(); // Show print button
                loadPackingListItems(poId, color);

                // Refresh PO-search so dropdown stays usable but empty
                setTimeout(reinitializePOSearch, 100);
            } else {
                $('.add-item').prop('disabled', true);
                $('#items_section').hide();
                $('#print_section').hide(); // Hide print button
                $('#itemsTable tbody').empty();
            }
        });

        // Print button click handler — opens print page in a NEW tab without opener reference
        $(document).on('click', '#printBtn', function() {
            if (!currentPoId || !currentColor) {
                Swal.fire({
                    title: 'Warning!',
                    text: 'Please select PO and Color first',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Opening Print...');

            var printUrl = '{{ route("auto_packing_list_print", ["po_id" => ":po_id", "color" => ":color"]) }}';
            printUrl = printUrl.replace(':po_id', currentPoId).replace(':color', encodeURIComponent(currentColor));

            // Open via temporary anchor with rel="noopener noreferrer"
            // This avoids creating a window.opener reference (prevents cross-window interactions)
            setTimeout(function() {
                var a = document.createElement('a');
                a.href = printUrl;
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                // append to body so click works consistently
                document.body.appendChild(a);
                a.click();
                a.remove();
            }, 50);

            // restore button quickly
            setTimeout(function() {
                $btn.prop('disabled', false).html('<i class="fas fa-print"></i> Print Packing List');
            }, 1200);
        });

        function loadPackingListItems(poId, color = null) {
            var requestData = {
                po_id: poId
            };
            if (color) requestData.color = color;

            // Helper function to get weight by size
            function getWeightBySize(size) {
                var weights = {
                    'XS': 0.195, // 195g
                    'S': 0.20, // 200g
                    'M': 0.205, // 205g
                    'L': 0.21, // 210g
                    'XL': 0.215, // 215g
                    'XXL': 0.22, // 220g
                    '2/3Y': 0.16, // 160g
                    '3/4Y': 0.165, // 165g
                    '4/5Y': 0.17, // 170g
                    '5/6Y': 0.175, // 175g
                    '6/7Y': 0.18, // 180g
                    '7/8Y': 0.185, // 185g
                    '9/10Y': 0.19, // 190g
                    '11/12Y': 0.195, // 195g
                    '13/14Y': 0.20, // 200g
                };
                return weights[size] || 0.20;
            }

            $.ajax({
                url: '{{ route("auto_packing_list_items") }}',
                type: 'GET',
                data: requestData,
                success: function(response) {
                    var packingLists = response.packing_lists;
                    var canAddItems = response.can_add_items;

                    $('.add-item').prop('disabled', !canAddItems);

                    var $container = $('#packing_lists_container').empty();

                    if (packingLists.length) {
                        packingLists.forEach(function(packingList, index) {
                            // Group items by carton name and maintain order
                            var itemsByCarton = {};
                            var cartonOrder = [];

                            packingList.items.forEach(function(item) {
                                if (!itemsByCarton[item.carton_name]) {
                                    itemsByCarton[item.carton_name] = [];
                                    cartonOrder.push(item.carton_name);
                                }
                                itemsByCarton[item.carton_name].push(item);
                            });

                            var tableHtml = `
                            <div class="mb-3">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th rowspan="2">Ctn. #</th>
                                                <th rowspan="2">PO No.</th>
                                                <th rowspan="2">SAP Article No.</th>
                                                <th rowspan="2">Short Desc.</th>
                                                <th rowspan="2">EAN / SKU</th>
                                                <th rowspan="2">Size</th>
                                                <th rowspan="2">Qty</th>
                                                <th colspan="3">Ctn. Mea (cm)</th>
                                                <th rowspan="2">Net Wt</th>
                                                <th rowspan="2">Gross Wt</th>
                                                <th rowspan="2">CBM</th>
                                            </tr>
                                            <tr>
                                                <th>L</th>
                                                <th>B</th>
                                                <th>H</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                `;

                            if (cartonOrder.length > 0) {
                                var grandQty = 0;
                                var grandNet = 0;
                                var grandGross = 0;

                                // Process cartons in order
                                cartonOrder.forEach(function(cartonName) {
                                    var items = itemsByCarton[cartonName];
                                    var itemCount = items.length;

                                    // Calculate net weight for entire carton based on all items and their sizes
                                    var cartonNetWeight = 0;
                                    items.forEach(function(item) {
                                        var weightPerPiece = getWeightBySize(item.size);
                                        cartonNetWeight += item.quantity * weightPerPiece;
                                    });

                                    var grossWeight = cartonNetWeight > 0 ? cartonNetWeight + 1.2 : 0;
                                    grandNet += cartonNetWeight;
                                    grandGross += grossWeight;

                                    items.forEach(function(item, itemIndex) {
                                        var length = item.carton ? item.carton.length || 0 : 0;
                                        var breadth = item.carton ? item.carton.breadth || 0 : 0;
                                        var height = item.carton ? item.carton.height || 0 : 0;

                                        var cbm = 0;
                                        if (length > 0 && breadth > 0 && height > 0) {
                                            cbm = item.quantity * (length * breadth * height) / 1000000;
                                        }

                                        grandQty += parseFloat(item.quantity) || 0;

                                        tableHtml += `<tr data-id="${item.id}">`;

                                        // Carton name (rowspan for first item only)
                                        if (itemIndex === 0) {
                                            tableHtml += `<td rowspan="${itemCount}" style="vertical-align: middle; font-weight: bold;">${cartonName}</td>`;
                                        }

                                        tableHtml += `
                                        <td>${packingList.po_no || ''}</td>
                                        <td>${item.article_number}</td>
                                        <td>${item.article_description || ''}</td>
                                        <td>${item.ean_code || ''}</td>
                                        <td>${item.size}</td>
                                        <td>${item.quantity}</td>
                                        <td>${length > 0 ? length : ''}</td>
                                        <td>${breadth > 0 ? breadth : ''}</td>
                                        <td>${height > 0 ? height : ''}</td>
                                    `;

                                        // Net Weight and Gross Weight (rowspan for first item only)
                                        if (itemIndex === 0) {
                                            tableHtml += `
                                            <td rowspan="${itemCount}" style="vertical-align: middle;">${cartonNetWeight > 0 ? cartonNetWeight.toFixed(2) : ''}</td>
                                            <td rowspan="${itemCount}" style="vertical-align: middle;">${grossWeight > 0 ? grossWeight.toFixed(2) : ''}</td>
                                        `;
                                        }

                                        tableHtml += `
                                        <td>${cbm > 0 ? cbm.toFixed(2) : ''}</td>
                                    </tr>`;
                                    });
                                });

                                // Add totals row
                                tableHtml += `
                                    <tr style="background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #007bff;">
                                        <td style="text-align: center;"><strong>TOTAL</strong></td>
                                        <td colspan="5"></td>
                                        <td style="text-align: center;"><strong>${grandQty}</strong></td>
                                        <td colspan="3"></td>
                                        <td style="text-align: center;"><strong>${grandNet > 0 ? grandNet.toFixed(2) : ''}</strong></td>
                                        <td style="text-align: center;"><strong>${grandGross > 0 ? grandGross.toFixed(2) : ''}</strong></td>
                                        <td></td>
                                    </tr>
                                `;
                            } else {
                                tableHtml += `<tr><td colspan="13" class="text-center">No items found</td></tr>`;
                            }

                            tableHtml += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            `;
                            $container.append(tableHtml);
                        });
                    } else {
                        $container.append(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No packing lists found for this color.
                        </div>
                    `);
                    }

                    // Show status message
                    if (!canAddItems) {
                        $container.prepend(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            All items for this color have been fully packed. No more items can be added.
                        </div>
                    `);
                    }
                },
                error: function() {
                    $('#packing_lists_container').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading packing lists
                    </div>
                `);
                }
            });
        }
    });
</script>
@endpush