<div class="table-responsive">
    <!-- Custom Nav tabs -->
    <nav class="nav nav-style-6 nav-pills mb-3 nav-justified d-sm-flex d-block" role="tablist">
        <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#nav-summary" aria-selected="true">Summary</a>
        <a class="nav-link" data-bs-toggle="tab" role="tab" href="#nav-size" aria-selected="false">Size Wise</a>
    </nav>

    <!-- Tab content -->
    <div class="tab-content">
        <!-- Summary Tab -->
        <div class="tab-pane show active text-muted" id="nav-summary" role="tabpanel">
            <!-- Export Button -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Report for Date: {{ date('d-m-Y', strtotime($date)) }}</h5>
                <button type="button" class="btn btn-success" id="exportSummaryBtn">
                    <i class="fa fa-file-excel me-1"></i> Export to Excel
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Vendor</th>
                            <th>Packing Table No</th>
                            <th>Job No</th>
                            <th>PO Qty</th>
                            <th>ORS Qty</th>
                            <th>Packed</th>
                            <th>Yet to Pack</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaryData as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['vendor'] }}</td>
                            <td>{{ $row['packing_table_no'] }}</td>
                            <td>{{ $row['job_no'] }}</td>
                            <td class="text-end">{{ number_format($row['po_qty']) }}</td>
                            <td class="text-end">{{ number_format($row['ors_qty']) }}</td>
                            <td class="text-end">{{ number_format($row['packed']) }}</td>
                            <td class="text-end">{{ number_format($row['yet_to_pack']) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-danger">No packing data found for the selected date</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($summaryData) > 0)
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total:</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($summaryData, 'po_qty'))) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($summaryData, 'ors_qty'))) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($summaryData, 'packed'))) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($summaryData, 'yet_to_pack'))) }}</th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Size Wise Tab -->
        <div class="tab-pane text-muted" id="nav-size" role="tabpanel">
            <!-- Export Button -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Size Wise Report for Date: {{ date('d-m-Y', strtotime($date)) }}</h5>
                <button type="button" class="btn btn-success" id="exportSizeWiseBtn">
                    <i class="fa fa-file-excel me-1"></i> Export to Excel
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Vendor</th>
                            <th>Packing Table No</th>
                            <th>Job No</th>
                            <th>Color</th>
                            <th>PO Qty</th>
                            <th>ORS Qty</th>
                            @foreach($allSizes as $size)
                            <th>{{ $size }}</th>
                            @endforeach
                            <th>Packed</th>
                            <th>Yet to Pack</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sizeWiseData as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['vendor'] }}</td>
                            <td>{{ $row['packing_table_no'] }}</td>
                            <td>{{ $row['job_no'] }}</td>
                            <td>{{ $row['color'] }}</td>
                            <td class="text-end">{{ number_format($row['po_qty']) }}</td>
                            <td class="text-end">{{ number_format($row['ors_qty']) }}</td>
                            @foreach($allSizes as $size)
                            <td class="text-end">{{ number_format($row['size_wise_packed'][$size] ?? 0) }}</td>
                            @endforeach
                            <td class="text-end">{{ number_format($row['packed']) }}</td>
                            <td class="text-end">{{ number_format($row['yet_to_pack']) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 10 + count($allSizes) }}" class="text-center text-danger">No packing data found for the selected date</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($sizeWiseData) > 0)
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total:</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($sizeWiseData, 'po_qty'))) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($sizeWiseData, 'ors_qty'))) }}</th>
                            @foreach($allSizes as $size)
                            <th class="text-end">
                                {{ number_format(array_sum(array_map(function($row) use ($size) {
                                    return $row['size_wise_packed'][$size] ?? 0;
                                }, $sizeWiseData))) }}
                            </th>
                            @endforeach
                            <th class="text-end">{{ number_format(array_sum(array_column($sizeWiseData, 'packed'))) }}</th>
                            <th class="text-end">{{ number_format(array_sum(array_column($sizeWiseData, 'yet_to_pack'))) }}</th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for exports -->
<form id="exportForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="date" id="export_date" value="{{ $date }}">
</form>