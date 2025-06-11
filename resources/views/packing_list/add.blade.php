@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
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
                        Packing List Entry
                    </div>
                </div>
                <div class="card-body">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="po_search">Search PO/Vendor</label>
                                <select class="form-control select2-po-search"
                                    id="po_search"
                                    name="po_id"
                                    data-placeholder="Search by PO Number or Vendor Name"
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
                                <h5>Carton Items</h5>
                                <button type="button" class="btn btn-success btn-sm add-item" disabled>
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
                                    <!-- Dynamic rows will be added here -->
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
            })
            .on('hidden.bs.modal', function() {
                togglePOSearch(true);
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

        // Handle color selection - Load table and reinitialize PO search
        $('#colorSelect').on('change', function() {
            var color = $(this).val();
            var poId = $('#po_search').val();

            if (color && poId) {
                $('.add-item').prop('disabled', false);
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
                success: function(items) {
                    var $tbody = $('#itemsTable tbody').empty();
                    if (items.length) {
                        items.forEach(function(item) {
                            const delBtn = items.length > 1 ?
                                `<button class="btn btn-danger btn-sm me-1 remove-item" data-id="${item.id}"><i class="fas fa-trash"></i></button>` :
                                '';
                            $tbody.append(`
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
                                        ${delBtn}
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        $tbody.append(`<tr><td colspan="5" class="text-center">No items found</td></tr>`);
                    }
                },
                error: function() {
                    $('#itemsTable tbody').html(`<tr><td colspan="5" class="text-center text-danger">Error loading items</td></tr>`);
                }
            });
        }

        // Add Item button
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

        // Edit Item button
        $(document).on('click', '.edit-item', function() {
            var itemId = $(this).data('id'),
                poId = $('#po_search').val();

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
            var itemId = $(this).data('id');
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
                        let options = '<option value="">Select Size</option>';
                        data.forEach(function(item) {
                            if (item.remaining_qty > 0) {
                                let qtyText = '';

                                if (item.packed_qty && item.packed_qty > 0) {
                                    qtyText = `(Rem Qty:${item.remaining_qty})`;
                                } else {
                                    qtyText = `(Packed Qty: ${item.remaining_qty})`;
                                }

                                options += `<option value="${item.size}" data-max-qty="${item.remaining_qty}" data-config-id="${item.config_item_id}">
                                    ${item.size} ${qtyText}
                                </option>`;
                            }
                        });
                        $('#sizeSelect').html(options).prop('disabled', false);
                        $('#quantityInput').val('').prop('max', 0);
                    }
                });
            } else {
                $('#sizeSelect').html('<option value="">Select Size</option>').prop('disabled', true);
                $('#quantityInput').val('').prop('max', 0);
            }
        });

        $(document).on('change', '#sizeSelect', function() {
            const maxQty = parseInt($(this).find(':selected').data('max-qty')) || 0;
            $('#quantityInput').attr('max', maxQty).val('');
            if (maxQty > 0) {
                $('#quantityInput').prop('disabled', false);
            }
        });

        $(document).on('input', '#quantityInput', function() {
            const maxQty = parseInt($('#sizeSelect').find(':selected').data('max-qty')) || 0;
            const currentQty = parseInt($(this).val()) || 0;

            if (currentQty > maxQty) {
                $(this).val(maxQty);
                Swal.fire({
                    icon: 'warning',
                    title: 'Quantity Limit',
                    text: `Maximum available quantity is ${maxQty}`,
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        $(document).on('click', '#saveItemBtn', function() {
            const isEdit = $('#itemId').length > 0;
            const data = {
                po_id: $('#po_id').val(),
                carton_id: $('#carton_id').val(),
                article_number: $('#articleSelect').val(),
                color: $('#color').val(),
                size: $('#sizeSelect').val(),
                quantity: $('#quantityInput').val(),
                config_item_id: $('#sizeSelect').find(':selected').data('config-id')
            };

            if (isEdit) {
                data.id = $('#itemId').val();
            }

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
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#add_modal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: isEdit ? 'Item updated successfully!' : 'Item added successfully!',
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
        });
    });
</script>
@endpush