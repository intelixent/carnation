@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
@push('styles')
<style>
    .dataTable {
        font-size: 12px !important;
    }

    .dataTable td,
    .dataTable th {
        font-size: 12px !important;
        padding: 0.5rem !important;
    }

    .text-right {
        text-align: right !important;
    }

    .dropdown-toggle::after {
        margin-left: 0.5em;
    }
</style>
<div class="container-fluid">
    <div class="modal fade" id="detail_modal"></div>

    <div class="row mt-2">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="accordion accordion-primary" id="accordionPrimaryExample">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPrimaryOne">
                                <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrimaryOne" aria-expanded="true" aria-controls="collapsePrimaryOne">
                                    Filters
                                </button>
                            </h2>
                            <div id="collapsePrimaryOne" class="accordion-collapse collapse" aria-labelledby="headingPrimaryOne" data-bs-parent="#accordionPrimaryExample">
                                <div class="accordion-body p-2">
                                    <div class="row mb-2">
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label for="vendor_id" class="form-label small">Vendor</label>
                                                <select name="vendor_id" id="vendor_id" class="form-control select2">
                                                    <option value="">Select Vendor</option>
                                                    @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-12 d-flex justify-content-end align-items-end">
                                            <button id="apply-filters" class="btn btn-sm btn-primary">Go</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Job Order Master
                    </div>
                </div>
                <div class="filter-summary px-3 py-2 border-bottom" style="display: none;">
                    <span class="text-muted">Active Filters:</span>
                    <div class="d-flex flex-wrap gap-2 mt-1" id="activeFilters"></div>
                </div>
                <div class="card-body">
                    <div class="table-responsive mt-2">
                        <table class="table table-bordered text-nowrap w-100 dataTable" id="jobDataTable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Job No</th>
                                    <th>Vendor</th>
                                    <th>Style</th>
                                    <th>Color</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
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
<script type="text/javascript">
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.select2').select2();

        var table = $('#jobDataTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('job_order_table') }}",
                type: 'POST',
                data: function(d) {
                    d.vendor_id = $('#vendor_id').val();
                },
                error: function(xhr, error, thrown) {
                    console.log('Ajax error:', error);
                }
            },
            columns: [
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'job_no_actions',
                    name: 'job_no',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'vendor_name',
                    name: 'vendor.name',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'style',
                    name: 'style',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'color',
                    name: 'color',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'status_badge',
                    name: 'status',
                    orderable: true,
                    searchable: false
                }
            ],
            pageLength: 50,
            order: [
                [1, 'desc']
            ],
            responsive: true,
            stateSave: true,
            drawCallback: function(settings) {
                updateFilterSummary();
            }
        });

        function updateFilterSummary() {
            const filters = {
                vendor: {
                    element: $('#vendor_id'),
                    text: 'Vendor'
                }
            };

            let activeFilters = [];
            let hasActiveFilters = false;

            Object.entries(filters).forEach(([key, filter]) => {
                const $element = filter.element;
                const value = $element.val();

                if (value) {
                    hasActiveFilters = true;
                    const selectedText = $element.find('option:selected').text();
                    activeFilters.push(`
                        <span class="badge bg-light text-dark border">
                            ${filter.text}: ${selectedText}
                            <i class="fas fa-times ms-1 clear-filter" data-filter="${key}" style="cursor: pointer;"></i>
                        </span>
                    `);
                }
            });

            const $filterSummary = $('.filter-summary');
            const $activeFilters = $('#activeFilters');

            if (hasActiveFilters) {
                $activeFilters.html(activeFilters.join(''));
                $filterSummary.show();
            } else {
                $filterSummary.hide();
            }
        }

        $('#apply-filters').on('click', function() {
            table.ajax.reload();
            updateFilterSummary();
        });

        $(document).on('click', '.clear-filter', function() {
            const filterType = $(this).data('filter');
            const filterMap = {
                'vendor': '#vendor_id'
            };

            $(filterMap[filterType]).val('').trigger('change');

            table.ajax.reload();
            updateFilterSummary();
        });

        $(document).on('click', '.view-job-order', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{route('get_job_order_details')}}",
                method: 'POST',
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#detail_modal").html(response);
                    $("#detail_modal").modal('show');
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Could not load job order details.',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });

        $(document).on('click', '.delete-job-order', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action will permanently delete the job order!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('job_order_delete') }}",
                        method: 'POST',
                        data: {
                            id: id,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Job order has been deleted.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                table.ajax.reload(null, false);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message || 'Something went wrong.',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Could not delete the job order. Please try again later.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush