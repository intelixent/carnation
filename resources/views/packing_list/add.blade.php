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
                        Packing List Entry
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
                                <h5>Packing Lists</h5>
                                <button type="button" class="btn btn-success btn-sm add-item" disabled>
                                    <i class="fas fa-plus"></i> Add Item
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

        // Hook into modal events to disable/enable PO search
        $('#add_modal')
            .on('show.bs.modal', function() {
                togglePOSearch(false);

                // Check if it's edit mode and load sizes
                if ($('#itemId').length > 0) {
                    loadAvailableSizesForEdit();
                }
            })
            .on('hidden.bs.modal', function() {
                togglePOSearch(true);
            });

        // Load available sizes for edit mode
        function loadAvailableSizesForEdit() {
            const poId = $('#po_id').val();
            const color = $('#color').val();
            const articleNumber = $('#articleSelect').val();
            const originalSize = $('#originalSize').val();

            $.ajax({
                url: '{{ route("get_available_sizes") }}',
                method: 'GET',
                data: {
                    po_id: poId,
                    color: color,
                    article_number: articleNumber
                },
                success: function(response) {
                    const sizeSelect = $('#sizeSelect');
                    sizeSelect.empty().append('<option value="">Select Size</option>');

                    if (response.sizes && response.sizes.length > 0) {
                        response.sizes.forEach(function(size) {
                            const isSelected = size.size === originalSize ? 'selected' : '';
                            sizeSelect.append(`<option value="${size.size}" ${isSelected}>${size.size}</option>`);
                        });

                        // If original size is selected, trigger change to load its data
                        if (originalSize) {
                            sizeSelect.trigger('change');
                        }
                    } else {
                        sizeSelect.append('<option value="">No sizes available</option>');
                    }
                },
                error: function(xhr) {
                    console.error('Error loading sizes:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load available sizes'
                    });
                }
            });
        }

        // Handle size selection change in edit mode
        $(document).on('change', '#sizeSelect', function() {
            const selectedSize = $(this).val();
            const poId = $('#po_id').val();
            const color = $('#color').val();
            const articleNumber = $('#articleSelect').val();
            const itemId = $('#itemId').val();

            if (selectedSize) {
                checkSizeAvailability(poId, color, articleNumber, selectedSize, itemId);
            } else {
                resetSizeInfo();
            }
        });

        // Check size availability for edit mode
        function checkSizeAvailability(poId, color, articleNumber, size, itemId) {
            $.ajax({
                url: '{{ route("check_size_availability") }}',
                method: 'GET',
                data: {
                    po_id: poId,
                    color: color,
                    article_number: articleNumber,
                    size: size,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        updateSizeInfo(response.data);
                        enableQuantityInput(response.data);
                    } else {
                        resetSizeInfo();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to check size availability'
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error checking size availability:', xhr);
                    resetSizeInfo();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to check size availability'
                    });
                }
            });
        }

        // Update size information display for edit mode
        function updateSizeInfo(data) {
            $('#maxQtyDisplay').text(data.max_qty);
            $('#packedQtyDisplay').text(data.packed_qty);
            $('#availableQtyDisplay').text(data.remaining_qty);
            $('#sizeInfoCard').show();

            // Update config_item_id
            $('#currentConfigId').val(data.config_item_id);
        }

        // Enable quantity input with proper validation for edit mode
        function enableQuantityInput(data) {
            const quantityInput = $('#quantityInput');
            const maxAvailable = data.remaining_qty;

            quantityInput.prop('disabled', false);
            quantityInput.attr('max', maxAvailable);
            quantityInput.data('max-qty', maxAvailable);

            // Update help text
            $('#quantityHelp').text(`Available: ${maxAvailable}`);

            // Reset quantity if it exceeds available
            const currentQty = parseInt(quantityInput.val());
            if (currentQty > maxAvailable) {
                quantityInput.val(maxAvailable > 0 ? 1 : 0);
            }

            // Enable/disable save button based on availability
            if (maxAvailable > 0) {
                quantityInput.trigger('input');
            } else {
                $('#saveItemBtn').prop('disabled', true);
                quantityInput.addClass('is-invalid');
            }
        }

        // Reset size information for edit mode
        function resetSizeInfo() {
            $('#sizeInfoCard').hide();
            $('#quantityInput').prop('disabled', true).val('');
            $('#quantityHelp').text('Select a size first');
            $('#saveItemBtn').prop('disabled', true);
            $('#currentConfigId').val('');
        }

        // Handle quantity input validation for edit mode
        $(document).on('input', '#quantityInput', function() {
            const quantity = parseInt($(this).val());
            const maxQty = parseInt($(this).data('max-qty'));
            const saveBtn = $('#saveItemBtn');

            if (quantity > 0 && quantity <= maxQty) {
                saveBtn.prop('disabled', false);
                $(this).removeClass('is-invalid');
            } else {
                saveBtn.prop('disabled', true);
                $(this).addClass('is-invalid');
            }
        });

        $('#po_search').on('change', function() {
            var poId = $(this).val();
            if (poId) {
                // Reset dependent fields
                $('#colorSelect').prop('disabled', true).html('<option value="">Select Color</option>');
                $('.add-item').prop('disabled', true);
                $('#items_section').hide();
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
                        $('#po_details, #items_section').hide();
                    }
                });
            } else {
                $('#po_details, #items_section').hide();
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

        // Handle color selection - Load table and check for position-based vendors
        $('#colorSelect').on('change', function() {
            var color = $(this).val();
            var poId = $('#po_search').val();

            if (color && poId) {
                $('#items_section').show();
                loadPackingListItems(poId, color);

                // Refresh PO-search so dropdown stays usable but empty
                setTimeout(reinitializePOSearch, 100);
            } else {
                $('.add-item').prop('disabled', true);
                $('#items_section').hide();
                $('#itemsTable tbody').empty();
            }
        });

        function loadPackingListItems(poId, color = null) {
            var requestData = {
                po_id: poId
            };
            if (color) requestData.color = color;

            $.ajax({
                url: '{{ route("packing_list_items") }}',
                type: 'GET',
                data: requestData,
                success: function(response) {
                    var packingLists = response.packing_lists;
                    var canAddItems = response.can_add_items;
                    var isPositionBased = response.is_position_based;

                    // Enable/disable add button based on availability and vendor type
                    $('.add-item').prop('disabled', !canAddItems);

                    // Hide add button for position-based vendors
                    if (isPositionBased) {
                        $('.add-item').hide();
                    } else {
                        $('.add-item').show();
                    }

                    var $container = $('#packing_lists_container').empty();

                    // Show info message for position-based vendors
                    if (isPositionBased && packingLists.length > 0) {
                        $container.prepend(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Packing list automatically generated based on position configuration.
                        </div>
                    `);
                    }

                    if (packingLists.length) {
                        packingLists.forEach(function(packingList, index) {
                            var statusBadge = getStatusBadge(packingList.pack_status);

                            var tableHtml = `
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">
                                        <strong>Ref: ${packingList.pack_ref_no}</strong>
                                    </h6>
                                    <div>
                                        ${statusBadge}
                                    </div>
                                </div>
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Carton Name</th>
                                            <th>Article Number</th>
                                            <th>Color</th>
                                            <th>Size</th>
                                            <th>Quantity</th>
                                            ${!isPositionBased ? '<th>Action</th>' : ''}
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                            if (packingList.items.length) {
                                packingList.items.forEach(function(item) {
                                    const actionButtons = !isPositionBased && packingList.pack_status === 0 ?
                                        `<button class="btn btn-primary btn-sm me-1 edit-item" data-id="${item.id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    ${packingList.items.length > 1 ? 
                                        `<button class="btn btn-danger btn-sm remove-item" data-id="${item.id}">
                                            <i class="fas fa-trash"></i>
                                        </button>` : ''
                                    }` : '';

                                    tableHtml += `
                                    <tr data-id="${item.id}">
                                        <td>${item.carton_name}</td>
                                        <td>${item.article_number}</td>
                                        <td>${item.color}</td>
                                        <td>${item.size}</td>
                                        <td>${item.quantity}</td>
                                        ${!isPositionBased ? `<td>${actionButtons}</td>` : ''}
                                    </tr>
                                `;
                                });
                            } else {
                                const colspan = isPositionBased ? '5' : '6';
                                tableHtml += `<tr><td colspan="${colspan}" class="text-center">No items found</td></tr>`;
                            }

                            tableHtml += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                            $container.append(tableHtml);
                        });
                    } else {
                        const message = isPositionBased ?
                            'No packing configuration found for this color.' :
                            'No packing lists found for this color.';

                        $container.append(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> ${message}
                        </div>
                    `);
                    }

                    // Show status message for manual vendors
                    if (!isPositionBased && !canAddItems) {
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

        function getStatusBadge(status) {
            switch (status) {
                case 0:
                    return '<span class="badge bg-warning text-dark">In Packaging</span>';
                case 1:
                    return '<span class="badge bg-info text-dark">Packed & Ready for Invoice</span>';
                case 2:
                    return '<span class="badge bg-success text-dark">Invoiced</span>';
                default:
                    return '<span class="badge bg-secondary">Unknown</span>';
            }
        }

        // Add Item button - Only for non-position-based vendors
        $(document).on('click', '.add-item', function() {
            var poId = $('#po_search').val(),
                vendorId = $('#vendor_id').val(),
                color = $('#colorSelect').val();

            if (!poId || !color) {
                Swal.fire({
                    icon: 'warning',
                    title: poId ? 'Color Required' : 'PO Required',
                    text: poId ? 'Please select a color first' : 'Please select a PO first',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // Check if this is a position-based vendor
            if ([1, 5, 6].includes(parseInt(vendorId))) {
                Swal.fire({
                    icon: 'info',
                    title: 'Auto-Generated Packing',
                    text: 'Packing list is automatically generated for this vendor based on configuration.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            $.ajax({
                url: "{{ route('packing_list_item_add') }}",
                method: 'POST',
                data: {
                    id: poId,
                    vendor_id: vendorId,
                    color: color
                },
                success: function(response) {
                    $("#add_modal").html(response);
                    // init any select2 inside modal
                    $('.select2m', '#add_modal').select2({
                        width: '100%',
                        dropdownParent: $('.modal-body')
                    });
                    $("#add_modal").modal('show');
                }
            });
        });

        // Edit Item button - Only for non-position-based vendors
        $(document).on('click', '.edit-item', function() {
            var itemId = $(this).data('id'),
                poId = $('#po_search').val(),
                vendorId = $('#vendor_id').val();

            // Check if this is a position-based vendor
            if ([1, 5, 6].includes(parseInt(vendorId))) {
                Swal.fire({
                    icon: 'info',
                    title: 'Cannot Edit',
                    text: 'Items cannot be edited for auto-generated packing lists.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            $.ajax({
                url: "{{ route('packing_list_item_edit') }}",
                method: 'POST',
                data: {
                    id: itemId,
                    po_id: poId
                },
                success: function(response) {
                    $("#add_modal").html(response);
                    $('.select2m', '#add_modal').select2({
                        width: '100%',
                        dropdownParent: $('.modal-body')
                    });
                    $("#add_modal").modal('show');
                }
            });
        });

        // Delete Item - Only for non-position-based vendors
        $(document).on('click', '.remove-item', function() {
            var itemId = $(this).data('id');
            var vendorId = $('#vendor_id').val();

            // Check if this is a position-based vendor
            if ([1, 5, 6].includes(parseInt(vendorId))) {
                Swal.fire({
                    icon: 'info',
                    title: 'Cannot Delete',
                    text: 'Items cannot be deleted from auto-generated packing lists.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: "{{ route('packing_list_item_delete') }}",
                    method: 'DELETE',
                    data: {
                        id: itemId
                    },
                    success: function() {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Item has been deleted.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadPackingListItems($('#po_search').val(), $('#colorSelect').val());
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            text: xhr.responseJSON?.error || 'Something went wrong',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            });
        });

        // Handle article selection - load sizes table (for non-position-based vendors)
        $(document).on('change', '.articleSelect', function() {
            const $block = $(this).closest('.article-block');
            const poId = $('#po_id').val();
            const color = $('#color').val();
            const article = $(this).val();
            const $container = $block.find('.sizesTableContainer');

            if (article) {
                $.ajax({
                    url: '{{ route("get_sizes_with_qty") }}',
                    data: {
                        po_id: poId,
                        color: color,
                        article_number: article
                    },
                    success: function(data) {
                        loadSizesTable(data, $container);
                    }
                });
            } else {
                $container.hide().empty();
            }
        });

        // Function to load sizes table (for non-position-based vendors)
        function loadSizesTable(sizes, $container) {
            let tableHtml = `
            <div class="sizes-table">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 60px;">
                                <input type="checkbox" class="selectAllSizes" id="selectAllSizes" class="form-check-input">
                            </th>
                            <th style="width: 40%;">Size</th>
                            <th style="width: 60%;">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            sizes.forEach(function(item) {
                if (item.remaining_qty > 0) {
                    let qtyText = '';
                    if (item.packed_qty && item.packed_qty > 0) {
                        qtyText = `Available: ${item.remaining_qty}`;
                    } else {
                        qtyText = `Pack Qty: ${item.remaining_qty}`;
                    }

                    tableHtml += `
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input size-checkbox" value="${item.size}" data-max-qty="${item.remaining_qty}" data-config-id="${item.config_item_id}">
                        </td>
                        <td>
                            <strong>${item.size}</strong><br>
                            <small class="text-muted">${qtyText}</small>
                        </td>
                        <td>
                            <input type="number" class="form-control quantity-input w-100" style="min-width: 120px;" min="1" max="${item.remaining_qty}" data-size="${item.size}" data-max-qty="${item.remaining_qty}" data-config-id="${item.config_item_id}" disabled>
                        </td>
                    </tr>
                `;
                }
            });

            tableHtml += `
                    </tbody>
                </table>
            </div>
        `;

            $container.html(tableHtml).show();
        }

        // Handle select all sizes checkbox
        $(document).on('change', '.selectAllSizes', function() {
            const $block = $(this).closest('.article-block');
            const isChecked = $(this).is(':checked');

            $block.find('.size-checkbox').prop('checked', isChecked).trigger('change');
        });

        // Handle individual size checkbox
        $(document).on('change', '.size-checkbox', function() {
            const $row = $(this).closest('tr');
            const isChecked = $(this).is(':checked');
            const $input = $row.find('.quantity-input');

            $input.prop('disabled', !isChecked);

            if (!isChecked) {
                $input.val('');
            } else {
                $input.focus();
            }

            // Update the "select all" checkbox in this block
            const $block = $(this).closest('.article-block');
            const total = $block.find('.size-checkbox').length;
            const checked = $block.find('.size-checkbox:checked').length;

            $block.find('.selectAllSizes').prop('checked', total === checked);
        });

        // Handle quantity input validation
        $(document).on('input', '.quantity-input', function() {
            const maxQty = parseInt($(this).data('max-qty')) || 0;
            const currentQty = parseInt($(this).val()) || 0;

            if (currentQty > maxQty) {
                $(this).val(maxQty);
                Swal.fire({
                    icon: 'warning',
                    title: 'Quantity Limit',
                    text: `Maximum available quantity is ${maxQty}`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });

        // Handle save item button - Updated to handle both add and edit modes
        $(document).on('click', '#saveItemBtn', function() {
            const isEdit = $('#itemId').length > 0;

            if (isEdit) {
                // Edit mode - validate inputs
                const selectedSize = $('#sizeSelect').val();
                const quantity = $('#quantityInput').val();

                if (!selectedSize) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Size Required',
                        text: 'Please select a size'
                    });
                    return;
                }

                if (!quantity || parseInt(quantity) <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Quantity Required',
                        text: 'Please enter a valid quantity'
                    });
                    return;
                }

                const data = {
                    id: $('#itemId').val(),
                    po_id: $('#po_id').val(),
                    carton_id: $('#carton_id').val(),
                    article_number: $('#articleSelect').val(),
                    color: $('#color').val(),
                    quantity: quantity,
                    size: selectedSize,
                    config_item_id: $('#currentConfigId').val()
                };
                saveItem(data, true);
            } else {
                // Add mode - collect data from multiple articles
                const allArticlesData = [];
                $('.article-block').each(function() {
                    const $block = $(this);
                    const article_number = $block.find('.articleSelect').val();
                    const selectedSizes = [];

                    $block.find('.size-checkbox:checked').each(function() {
                        const size = $(this).val();
                        const quantity = $block.find(`.quantity-input[data-size="${size}"]`).val();
                        const configId = $(this).data('config-id');

                        if (quantity && parseInt(quantity) > 0) {
                            selectedSizes.push({
                                size: size,
                                quantity: parseInt(quantity),
                                config_item_id: configId,
                            });
                        }
                    });

                    if (selectedSizes.length > 0) {
                        allArticlesData.push({
                            article_number,
                            color: $('#color').val(),
                            net_weight: $("#net_weight").val(),
                            sizes: selectedSizes
                        });
                    }
                });

                if (allArticlesData.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Items Selected',
                        text: 'Please select at least one size with quantity',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // Call batch save
                saveMultipleItems(allArticlesData);
            }
        });

        // Single item save function (for edit mode)
        function saveItem(data, isEdit = false) {
            const url = isEdit ? '{{ route("packing_list_item_update") }}' : '{{ route("packing_list_item_store") }}';

            Swal.fire({
                title: isEdit ? 'Updating...' : 'Saving...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function(response) {
                    $('#add_modal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: isEdit ? 'Item updated successfully!' : 'Item saved successfully!',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    if (response.po_id) {
                        const color = $('#colorSelect').val() || $('#color').val();
                        loadPackingListItems(response.po_id, color);
                    }
                },
                error: function(xhr) {
                    handleSaveError(xhr);
                }
            });
        }

        // Batch save function (for add mode)
        function saveMultipleItems(allArticlesData) {
            const po_details = {
                po_id: $('#po_id').val(),
                carton_id: $('#carton_id').val(),
                color: $('#color').val(),
                net_weight: $("#net_weight").val()
            };

            Swal.fire({
                title: 'Saving Items...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '{{ route("packing_list_item_store") }}',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    cartondata: allArticlesData,
                    po_details: po_details
                }),
                success: function(response) {
                    $('#add_modal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: `Items added successfully!`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    if (response.po_id) {
                        const color = $('#colorSelect').val();
                        loadPackingListItems(response.po_id, color);
                    }
                },
                error: function(xhr) {
                    handleSaveError(xhr);
                }
            });
        }

        // Error handler function for save operations
        function handleSaveError(xhr) {
            Swal.close();

            let errorMessage = 'An error occurred while saving';

            if (xhr.responseJSON) {
                if (xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.responseJSON.errors) {
                    // Handle validation errors
                    const errors = xhr.responseJSON.errors;
                    const firstError = Object.values(errors)[0];
                    errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                } else if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    // If not JSON, use status text
                    errorMessage = xhr.statusText || errorMessage;
                }
            }

            Swal.fire({
                title: 'Error!',
                text: errorMessage,
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
        }

        // Dynamic save button enablement for add mode
        $(document).on('change input', '.size-checkbox, .quantity-input', function() {
            updateSaveButtonState();
        });

        function updateSaveButtonState() {
            let hasValidItems = false;

            $('.article-block').each(function() {
                const $block = $(this);
                const articleSelected = $block.find('.articleSelect').val();

                if (articleSelected) {
                    $block.find('.size-checkbox:checked').each(function() {
                        const size = $(this).val();
                        const quantity = $block.find(`.quantity-input[data-size="${size}"]`).val();

                        if (quantity && parseInt(quantity) > 0) {
                            hasValidItems = true;
                            return false; // break out of each loop
                        }
                    });
                }

                if (hasValidItems) {
                    return false; // break out of each loop
                }
            });

            $('#saveItemBtn').prop('disabled', !hasValidItems);
        }

        // Initialize save button state when modal opens
        $(document).on('shown.bs.modal', '#add_modal', function() {
            // Small delay to ensure DOM is ready
            setTimeout(updateSaveButtonState, 100);
        });
    });
</script>
@endpush