@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
        color: white;
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
                        Edit Packing List
                    </div>
                </div>
                <div class="card-body">
                    @csrf
                    <input type="hidden" id="packing_list_id" value="{{ $packingList->id }}">
                    <input type="hidden" id="po_id" value="{{ $packingList->po_id }}">
                    <input type="hidden" id="vendor_id" value="{{ $packingList->vendor_id }}">


                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Color</label>
                            <select class="form-control select2" id="colorSelect" required disabled>
                                <option value="">Select Color</option>
                            </select>
                        </div>
                    </div>

                    <!-- PO Details Display -->
                    <div class="row mt-3" id="po_details">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>PO Number:</strong> <span id="po_num">{{ $packingList->po_no }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>Job Number:</strong> <span id="po_job_num">{{ $packingList->po->po_job_num }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>PO Date:</strong> <span id="po_date">{{ $packingList->po_date }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>Vendor:</strong> <span id="vendor_name">{{ $packingList->vendor->name }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carton Items Table -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Carton Items</h5>
                                <button type="button" class="btn btn-success btn-sm add-item">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            </div>
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Carton Name</th>
                                        <th>Article Number</th>
                                        <th>Color</th>
                                        <th>Size</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic rows will be loaded here -->
                                </tbody>
                            </table>
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

        $('.select2').select2();

        const poId = $('#po_id').val()

        loadColors(poId);

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

        // Load packing list items on page load
        loadPackingListItems();

        function loadPackingListItems() {
            const packingListId = $('#packing_list_id').val();

            $.ajax({
                url: '{{ route("packing_list_items_by_id") }}',
                type: 'GET',
                data: {
                    packing_list_id: packingListId
                },
                success: function(items) {
                    $('#itemsTable tbody').empty();

                    if (items.length > 0) {
                        items.forEach(function(item) {
                            const deleteButton = items.length > 1 ?
                                `<button class="btn btn-danger btn-sm me-1 remove-item" data-id="${item.id}">
                                    <i class="fas fa-trash"></i>
                                </button>` : '';

                            $('#itemsTable tbody').append(`
                                <tr data-id="${item.id}">
                                    <td>${item.carton_name}</td>
                                    <td>${item.article_number}</td>
                                    <td>${item.color}</td>
                                    <td>${item.size}</td>
                                    <td>${item.quantity}</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm me-1 edit-item" data-id="${item.id}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        ${deleteButton}
                                    </td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error loading items');
                }
            });
        }

        // Add Item button
        $(document).on('click', '.add-item', function() {
            const poId = $('#po_id').val();
            const vendorId = $('#vendor_id').val();
            const color = $('#colorSelect').val();

            if (!poId || !color) {
                Swal.fire({
                    icon: 'warning',
                    title: poId ? 'Color Required' : 'PO Required',
                    text: poId ? 'Please select a color first' : 'Please select a PO first',
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
                    color: color,
                    packing_list_id: $('#packing_list_id').val()
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

        // Edit Item button - FIXED: Removed duplicate AJAX call
        $(document).on('click', '.edit-item', function() {
            const itemId = $(this).data('id');
            const poId = $('#po_id').val();

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

        // Delete Item
        $(document).on('click', '.remove-item', function() {
            const itemId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('packing_list_item_delete') }}",
                        method: 'DELETE',
                        data: {
                            id: itemId
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Item has been deleted successfully.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            loadPackingListItems();
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
                }
            });
        });

        // Handle article selection - load sizes table (UPDATED to match new structure)
        $(document).on('change', '#articleSelect', function() {
            const poId = $('#po_id').val();
            const color = $('#color').val();
            const article = $(this).val();

            if (article) {
                $.ajax({
                    url: '{{ route("get_sizes_with_qty") }}',
                    data: {
                        po_id: poId,
                        color: color,
                        article_number: article
                    },
                    success: function(data) {
                        loadSizesTable(data);
                    }
                });
            } else {
                $('#sizesTableContainer').hide();
            }
        });

        // Function to load sizes table (ADDED from main script)
        function loadSizesTable(sizes) {
            let tableHtml = `
                <div class="sizes-table">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 60px;">
                                    <input type="checkbox" id="selectAllSizes" class="form-check-input">
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

            $('#sizesTableContainer').html(tableHtml).show();
        }

        // Handle select all sizes checkbox (ADDED from main script)
        $(document).on('change', '#selectAllSizes', function() {
            const isChecked = $(this).is(':checked');
            $('.size-checkbox').prop('checked', isChecked).trigger('change');
        });

        // Handle individual size checkbox (ADDED from main script)
        $(document).on('change', '.size-checkbox', function() {
            const isChecked = $(this).is(':checked');
            const size = $(this).val();
            const quantityInput = $(`.quantity-input[data-size="${size}"]`);

            quantityInput.prop('disabled', !isChecked);
            if (!isChecked) {
                quantityInput.val('');
            } else {
                quantityInput.focus();
            }

            // Update select all checkbox
            const totalCheckboxes = $('.size-checkbox').length;
            const checkedCheckboxes = $('.size-checkbox:checked').length;
            $('#selectAllSizes').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        // Handle quantity input validation (ADDED from main script)
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

        // Handle save item button (UPDATED to match new structure)
        $(document).on('click', '#saveItemBtn', function() {
            const isEdit = $('#itemId').length > 0;

            if (isEdit) {
                // Edit mode - get data from form
                const data = {
                    id: $('#itemId').val(),
                    po_id: $('#po_id').val(),
                    carton_id: $('#carton_id').val(),
                    article_number: $('#articleSelect').val(),
                    color: $('#color').val(),
                    quantity: $('#quantityInput').val(),
                    size: $('#currentSize').val(),
                    config_item_id: $('#currentConfigId').val(),
                    packing_list_id: $('#packing_list_id').val()
                };

                saveItem(data, true);
            } else {
                // Add mode - collect data from sizes table
                const selectedSizes = [];
                $('.size-checkbox:checked').each(function() {
                    const size = $(this).val();
                    const quantity = $(`.quantity-input[data-size="${size}"]`).val();
                    const configId = $(this).data('config-id');

                    if (quantity && parseInt(quantity) > 0) {
                        selectedSizes.push({
                            size: size,
                            quantity: parseInt(quantity),
                            config_item_id: configId
                        });
                    }
                });

                if (selectedSizes.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Items Selected',
                        text: 'Please select at least one size with quantity',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // Save multiple items
                const baseData = {
                    po_id: $('#po_id').val(),
                    carton_id: $('#carton_id').val(),
                    article_number: $('#articleSelect').val(),
                    color: $('#color').val(),
                    packing_list_id: $('#packing_list_id').val()
                };

                saveMultipleItems(baseData, selectedSizes);
            }
        });

        // Function to save single item (for edit)
        function saveItem(data, isEdit = false) {
            const url = isEdit ? '{{ route("packing_list_item_update") }}' : '{{ route("packing_list_item_store") }}';

            Swal.fire({
                title: isEdit ? 'Updating...' : 'Saving...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
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

                    loadPackingListItems();
                },
                error: function(xhr) {
                    handleSaveError(xhr);
                }
            });
        }

        // Function to save multiple items (for add)
        function saveMultipleItems(baseData, selectedSizes) {
            Swal.fire({
                title: 'Saving Items...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const promises = selectedSizes.map(sizeData => {
                const itemData = {
                    ...baseData,
                    size: sizeData.size,
                    quantity: sizeData.quantity,
                    config_item_id: sizeData.config_item_id
                };

                return $.ajax({
                    url: '{{ route("packing_list_item_store") }}',
                    method: 'POST',
                    data: itemData
                });
            });

            Promise.all(promises)
                .then(responses => {
                    $('#add_modal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: `${selectedSizes.length} items added successfully!`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    loadPackingListItems();
                })
                .catch(xhr => {
                    handleSaveError(xhr);
                });
        }

        // Function to handle save errors
        function handleSaveError(xhr) {
            let errorMessage = 'Something went wrong';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            }

            Swal.fire({
                title: 'Error!',
                html: errorMessage,
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
        }
    });
</script>
@endpush