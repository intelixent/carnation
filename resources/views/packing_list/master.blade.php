@extends('layouts.app')
@section('pagetitle', $page_title)
@section('content')
@push('styles')
<style>
    .error-border {
        border: 1px solid #dc3545 !important;
    }

    .error {
        color: #dc3545;
        font-size: 80%;
        margin-top: 0.25rem;
        display: block;
    }

    .checkbox-group {
        position: relative;
        padding-bottom: 20px;
    }

    .checkbox-group .error {
        position: absolute;
        bottom: 0;
        left: 0;
    }

    .form-check {
        transition: none;
        transform: none !important;
    }

    .d-flex.flex-column.gap-2 {
        position: relative;
    }
</style>
@endpush

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

    <div class="modal fade" id="detail_modal"></div>

    <!-- Packing PO Number Modal -->
    <div class="modal fade" id="packingPoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-dark">
                    <h5 class="modal-title">Packing PO Number</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="packingPoForm">
                    <div class="modal-body">
                        <input type="hidden" id="packing_list_id" name="packing_list_id">
                        <div class="mb-3">
                            <label for="packing_po_num" class="form-label">Packing PO Number</label>
                            <input type="text" class="form-control" id="packing_po_num" name="packing_po_num" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Packing List Master
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100 dataTable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Ref no</th>
                                    <th>Job Number</th>
                                    <th>PO Number</th>
                                    <th>Vendor Name</th>
                                    <th>Packed at</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($packingLists as $key => $packingList)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ $packingList->pack_ref_no }}
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                <li><a class="dropdown-item view-pl" data-id="{{ $packingList->id }}" href="javascript:void(0);">View</a></li>
                                                <!-- <li><a class="dropdown-item edit-pl" href="{{ route('packing_list_edit', ['id' => $packingList->id]) }}">Edit</a></li> -->

                                                <li><a class="dropdown-item complete-pl" data-id="{{ $packingList->id }}" href="javascript:void(0);">Mark as Complete</a></li>
                                                <li><a class="dropdown-item print-pl" target="_blank" href="{{ route('packing_list_print', ['id' => $packingList->id]) }}">Packing List Print</a></li>
                                                <li><a class="dropdown-item delete-pl" data-id="{{ $packingList->id }}" href="javascript:void(0);">Delete</a></li>

                                                @if($packingList->vendor_id == 2)
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item add-packing-po-number-modal"
                                                        data-id="{{ $packingList->id }}"
                                                        data-current="{{ $packingList->packing_po_num ?? '' }}"
                                                        href="javascript:void(0);">
                                                        {{ $packingList->packing_po_num ? 'Update Packing PO Number' : 'Add Packing PO Number' }}
                                                    </a>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                    <td>{{ $packingList->po->po_job_num }}</td>
                                    <td>{{ $packingList->po_no }}</td>
                                    <td>{{ $packingList->po->vendor->name ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($packingList->created_at)->format('d-m-Y h:i A'); }}</td>
                                    <td>
                                        @php
                                        if($packingList->pack_status==0)
                                        {
                                        echo '<span class="badge bg-warning text-dark">In Packaging</span>';
                                        }
                                        elseif($packingList->pack_status==1){
                                        echo '<span class="badge bg-info text-dark">Packed & Ready for Invoice</span>';
                                        }
                                        elseif($packingList->pack_status==2){
                                        echo '<span class="badge bg-success text-dark">Invoiced</span>';
                                        }
                                        @endphp
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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

        var table = $(".dataTable").DataTable({
            "order": [
                [0, "asc"]
            ]
        });

        $(document).on('click', '.view-pl', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('packing_list_details')}}",
                method: 'POST',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#detail_modal").html(response);
                    $("#detail_modal").modal('show');
                }
            });
        });

        $(document).on('click', '.add-packing-po-number-modal', function() {
            const id = $(this).data('id');
            const current = $(this).data('current');

            $('#packing_list_id').val(id);
            $('#packing_po_num').val(current);

            $('#packingPoModal').modal('show');
        });

        $('#packingPoForm').submit(function(e) {
            e.preventDefault();

            const packingListId = $('#packing_list_id').val();
            const packingPoNumber = $('#packing_po_num').val();

            $.ajax({
                url: "{{ route('update_packing_list_po_num') }}",
                method: 'POST',
                data: {
                    packing_list_id: packingListId,
                    packing_po_num: packingPoNumber,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Could not update Packing PO number. Please try again.', 'error');
                }
            });
        });

        $(document).on('click', '.delete-pl', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action will permanently delete the packing list!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('packing_list_delete') }}",
                        method: 'POST',
                        data: {
                            id: id,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Packing list has been deleted.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Something went wrong.',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'Could not delete the packing list. Please try again later.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        $(document).on('click', '.complete-pl', function() {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "Mark this packing list as complete?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, complete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('packing_list_complete') }}",
                        method: 'POST',
                        data: {
                            id,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Done!', response.message, 'success')
                                    .then(() => window.location.reload());
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'Could not update status. Please try again.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush