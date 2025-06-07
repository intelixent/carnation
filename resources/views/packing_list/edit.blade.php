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
                                    <p class="mb-1"><strong>PO Date:</strong> <span id="po_date">{{ \Carbon\Carbon::parse($packingList->po_date)->format('d-m-Y') }}</span></p>
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

        $(document).on('click', '.add-item', function() {
            const poId = $('#po_id').val();
            const vendorId = $('#vendor_id').val();
            const color = $('#colorSelect').val();

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
                    $('.select2').select2({
                        width: '100%',
                        dropdownParent: $('.modal-body')
                    });
                    $("#add_modal").modal('show');
                }
            });
        });

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
                    $('.select2').select2({
                        width: '100%',
                        dropdownParent: $('.modal-body')
                    });
                    $("#add_modal").modal('show');
                }
            });
            $.ajax({
                url: "{{ route('packing_list_item_edit') }}",
                method: 'POST',
                data: {
                    id: itemId,
                    po_id: poId
                },
                success: function(response) {
                    $("#add_modal").html(response);
                    $('.select2', '#add_modal').select2({
                        width: '100%',
                        dropdownParent: $('.modal-body')
                    });
                    $("#add_modal").modal('show');
                }
            });
        });

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
                                options += `<option value="${item.size}" data-max-qty="${item.remaining_qty}" data-config-id="${item.config_item_id}">
                                    ${item.size} (Available: ${item.remaining_qty})
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
                packing_list_id: $('#packing_list_id').val(),
                config_item_id: $('#sizeSelect').find(':selected').data('config-id')
            };

            if (isEdit) {
                data.id = $('#itemId').val();
            }

            const url = isEdit ? '{{ route("packing_list_item_update") }}' : '{{ route("packing_list_item_store") }}';

            // Show loading
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

                    loadPackingListItems();
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