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
</style>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<div class="container-fluid">
    <!-- BreadCrumbs -->
    <!-- <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">@yield('pagetitle')</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_main_title}}</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_title}}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{$page_child_title}}</li>
                </ol>
            </nav>
        </div>
    </div> -->
    <!-- BreadCrumbs -->

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
                                                <label for="company_id" class="form-label small">Vendor</label>
                                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                                    <option value="">Select Vendor</option>
                                                    @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->name ? 'selected' : '' }}>
                                                        {{ $vendor->name }}
                                                    </option>
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
                        Purchase Order Master
                    </div>
                </div>
                <div class="filter-summary px-3 py-2 border-bottom" style="display: none;">
                    <span class="text-muted">Active Filters:</span>
                    <div class="d-flex flex-wrap gap-2 mt-1" id="activeFilters"></div>
                </div>
                <div class="card-body">
                    <nav class="nav nav-style-6 nav-pills mb-3 nav-justified d-sm-flex d-block" role="tablist">
                        <a class="nav-link" href="{{route('pdf_extract_all_master')}}">ALL</a>
                        <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#nav-amended" aria-selected="true">AMENDED</a>
                    </nav>
                    <div class="tab-content">
                        <input type="hidden" name="type" id="type" value="amended">
                        <div class="tab-pane show active text-muted" id="nav-bedroom" role="tabpanel">
                            <table class="table table-bordered text-nowrap w-100 dataTable">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Job No</th>
                                        <th>Vendor</th>
                                        <th>Po No</th>
                                        <th>PO Date</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- row closed -->
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.select2').select2();

        $('#date_range').daterangepicker({
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [
                    moment().subtract(1, 'month').startOf('month'),
                    moment().subtract(1, 'month').endOf('month')
                ]
            },
            alwaysShowCalendars: true,
            opens: 'left',
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            }
        }, function(start, end, label) {});

        $('#date_range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        var table = $(".dataTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('get_po_table') }}",
                type: 'GET',
                data: function(d) {
                    d.type = $('#type').val();
                    d.vendor_id = $('#vendor_id').val();

                    // var dateRange = $('#date_range').val();
                    // if (dateRange) {
                    //     var dates = dateRange.split(' - ');
                    //     d.from_date = dates[0];
                    //     d.to_date = dates[1];
                    // }
                },
                error: function(xhr, error, thrown) {
                    console.log('Ajax error:', error);
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'po_ref_num',
                    name: 'po_ref_num'
                },
                {
                    data: 'vendor',
                    name: 'vendor'
                },
                {
                    data: 'po_num',
                    name: 'po_num'
                },
                {
                    data: 'po_date',
                    name: 'po_date'
                },
                {
                    data: 'created',
                    name: 'created'
                }
            ],
            pageLength: 50,
            order: [
                [0, 'asc']
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

        $(document).on('click', '.po-details-link', function() {
            var poId = $(this).data('id');
            $.ajax({
                url: "{{route('get_po_details')}}",
                method: 'POST',
                data: {
                    po_id: poId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#detail_modal").html(response);
                    $("#detail_modal").modal('show');
                }
            });
        });
    });
</script>
@endpush