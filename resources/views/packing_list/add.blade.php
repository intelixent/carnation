@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
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
                    </div>

                    <!-- PO Details Display -->
                    <div class="row mt-3" id="po_details" style="display: none;">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>PO Number:</strong> <span id="po_num"></span></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>PO Date:</strong> <span id="po_date"></span></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Vendor:</strong> <span id="vendor_name"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add this after the PO details display -->
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

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
        color: white;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize Select2
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
                                text: item.po_num + ' || ' + item.vendor_name
                            }
                        })
                    };
                },
                cache: true
            }
        });

        $('#po_search').on('change', function() {
            var poId = $(this).val();
            if (poId) {
                $.ajax({
                    url: '{{ route("packing_list_details") }}',
                    type: 'GET',
                    data: {
                        id: poId
                    },
                    success: function(data) {
                        $('#po_num').text(data.po_num);
                        $('#po_date').text(data.po_date_formatted);
                        $('#vendor_name').text(data.vendor_name);
                        $('#po_details').show();
                    },
                    error: function(xhr) {
                        console.error('Error fetching PO details');
                        $('#po_details').hide();
                    }
                });
            } else {
                $('#po_details').hide();
            }
        });

        $(document).on('click', '.add-item', function() {
            const poId = $('#po_search').val();
            if (!poId) {
                alert('Please select a PO first');
                return;
            }

            $.ajax({
                url: "{{ route('packing_list_item_add') }}",
                method: 'POST',
                data: {
                    id: poId
                },
                success: function(response) {
                    $("#add_modal").html(response);
                    $("#add_modal").modal('show');
                }
            });
        });

        $(document).on('change', '#articleSelect', function() {
            const poId = $('#po_id').val();
            const article = $(this).val();

            $.ajax({
                url: '{{ route("packing_list_sizes") }}',
                data: {
                    po_id: poId,
                    article_number: article
                },
                success: function(data) {
                    $('#sizeSelect').html(data.options).prop('disabled', false);
                }
            });
        });

        $(document).on('click', '#saveItemBtn', function() {
            const data = {
                po_id: $('#po_id').val(),
                carton_id: $('#cartonSelect').val(),
                article_number: $('#articleSelect').val(),
                size: $('#sizeSelect').val(),
                quantity: $('#quantityInput').val()
            };

            $.ajax({
                url: '{{ route("packing_list_item_store") }}',
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Add to table
                    $('#itemsTable tbody').append(`
                <tr>
                    <td>${$('#cartonSelect option:selected').text()}</td>
                    <td>${data.article_number}</td>
                    <td>${data.size}</td>
                    <td>${data.quantity}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                        <input type="hidden" name="items[][carton_id]" value="${data.carton_id}">
                        <input type="hidden" name="items[][article_number]" value="${data.article_number}">
                        <input type="hidden" name="items[][size]" value="${data.size}">
                        <input type="hidden" name="items[][quantity]" value="${data.quantity}">
                    </td>
                </tr>
            `);

                    $('#add_modal').modal('hide');
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseJSON?.error || 'Something went wrong');
                }
            });
        });
    });
</script>
@endpush