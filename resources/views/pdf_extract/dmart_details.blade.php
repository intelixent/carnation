<div class="modal-dialog modal-xl">
    <div class="modal-content">
        @php
        $po = $data['po_master'];
        $article_info = $data['article_info'] ?? [];
        $carton_rows = $data['carton_qty_sizes']['data'] ?? collect();
        $carton_sizes = $data['carton_qty_sizes']['sizes'] ?? [];

        // Pivot the flat color/size rows into color => [size => qty], same shape
        // as the "Add Color" grid on the upload screen.
        $cartonPivot = [];
        foreach ($carton_rows as $row) {
        $color = $row->color ?? '';
        if (!isset($cartonPivot[$color])) {
        $cartonPivot[$color] = [];
        }
        $cartonPivot[$color][$row->size] = ($cartonPivot[$color][$row->size] ?? 0) + (int) $row->carton_qty;
        }

        // Case lot, ratio, total cartons, gst%, price, mrp, article description/ean/hsn are
        // identical on every carton row (same convention as createDmartItems) - pull them
        // once from the first row instead of repeating per line.
        $firstCartonRow = $carton_rows->first();
        @endphp
        <div class="modal-header">
            <h5 class="modal-title">Purchase Order Details - #{{ $po['po_ref_num'] ?? '' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="accordion" id="pdfAccordion">
                    {{-- PO DETAILS --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingPoDetails">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePoDetails" aria-expanded="true" aria-controls="collapsePoDetails">
                                PO Details
                            </button>
                        </h2>
                        <div id="collapsePoDetails" class="accordion-collapse collapse show" aria-labelledby="headingPoDetails" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>PO Information</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item"><strong>PO Number:</strong> {{ $po['po_num'] ?? '' }} - <strong>PO Date:</strong> {{ $po['po_date'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Goods Ready Date:</strong> {{ $po['goods_ready_date'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Total Qty:</strong> {{ $po['po_qty'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Unit Price:</strong> {{ $po['po_unit_price'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Total Boxes:</strong> {{ $article_info['total_boxes'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Total Value:</strong> {{ $article_info['total_value'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Amount in Words:</strong> {{ $article_info['amount_in_words'] ?? '' }}</li>
                                        </ul>
                                    </div>

                                    <div class="col-md-6">
                                        <h5>Buyer (Ship/Bill To)</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item"><strong>Name:</strong> {{ $article_info['buyer_name'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Address:</strong> {{ $po['vendor_del_adr'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>CIN:</strong> {{ $article_info['buyer_cin'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>GSTIN:</strong> {{ $article_info['buyer_gstin'] ?? '' }}</li>
                                        </ul>

                                        <h5>Vendor</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item"><strong>Name:</strong> {{ $article_info['vendor_name'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Address:</strong> {{ $po['vendor_com_adr'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Phone:</strong> {{ $article_info['vendor_phone'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Email:</strong> {{ $article_info['vendor_email'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>GSTIN:</strong> {{ $po['vendor_gst'] ?? '' }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PO ITEMS (as originally extracted from the PDF) --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingPoItems">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePoItems" aria-expanded="false" aria-controls="collapsePoItems">
                                PO Items
                            </button>
                        </h2>
                        <div id="collapsePoItems" class="accordion-collapse collapse" aria-labelledby="headingPoItems" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Sno</th>
                                                <th>EAN No</th>
                                                <th>Description</th>
                                                <th>HSN</th>
                                                <th>Delivery Date</th>
                                                <th>UOM</th>
                                                <th>Case Lot</th>
                                                <th>Boxes</th>
                                                <th>Qty</th>
                                                <th>B.Price</th>
                                                <th>Net Price</th>
                                                <th>CGST/IGST %</th>
                                                <th>L.Price</th>
                                                <th>MRP</th>
                                                <th>T.Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(($article_info['po_items'] ?? []) as $item)
                                            <tr>
                                                <td>{{ $item['sno'] ?? '' }}</td>
                                                <td>{{ $item['ean'] ?? '' }}</td>
                                                <td>{{ $item['description'] ?? '' }}</td>
                                                <td>{{ $item['hsn'] ?? '' }}</td>
                                                <td>{{ $item['delivery_dt'] ?? '' }}</td>
                                                <td>{{ $item['uom'] ?? '' }}</td>
                                                <td>{{ $item['case_lot'] ?? '' }}</td>
                                                <td>{{ $item['boxes'] ?? '' }}</td>
                                                <td>{{ $item['qty'] ?? '' }}</td>
                                                <td>{{ $item['b_price'] ?? '' }}</td>
                                                <td>{{ $item['net_price'] ?? '' }}</td>
                                                <td>{{ $item['cgst_igst_pct'] ?? '' }}</td>
                                                <td>{{ $item['l_price'] ?? '' }}</td>
                                                <td>{{ $item['mrp'] ?? '' }}</td>
                                                <td>{{ $item['t_value'] ?? '' }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="15" class="text-center">No PO items found.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="8">Total</td>
                                                <td>{{ $article_info['total_qty'] ?? '' }}</td>
                                                <td colspan="5">{{ $article_info['total_value'] ?? '' }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- CARTON QTY / SIZE BREAKDOWN (pivoted: color rows x size columns, same layout as the upload screen's size-chart grid) --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingCartonQty">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCartonQty" aria-expanded="false" aria-controls="collapseCartonQty">
                                Carton Qty / Size Breakdown
                            </button>
                        </h2>
                        <div id="collapseCartonQty" class="accordion-collapse collapse" aria-labelledby="headingCartonQty" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">

                                {{-- Values that are constant across every carton row --}}
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label"><strong>Article Description</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->article_description ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><strong>EAN</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->ean_code ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><strong>HSN</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->hsn_code ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><strong>Case Lot</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->case_lot ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label"><strong>Ratio</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->ratio ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><strong>Total Cartons</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->total_cartons ?? '' }}" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label"><strong>Total Qty</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->total_qty ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><strong>GST %</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->gst_percentage ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><strong>Price (Post GST)</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->price ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><strong>MRP</strong></label>
                                        <input type="text" class="form-control" value="{{ $firstCartonRow->mrp_price ?? '' }}" readonly>
                                    </div>
                                </div>

                                {{-- Color x Size pivot grid --}}
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th style="min-width:160px;">Color</th>
                                                @foreach($carton_sizes as $size)
                                                <th class="text-center">{{ $size }}</th>
                                                @endforeach
                                                <th class="text-center">Row Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($cartonPivot as $color => $sizeQtys)
                                            <tr>
                                                <td>{{ $color }}</td>
                                                @foreach($carton_sizes as $size)
                                                <td class="text-center">{{ $sizeQtys[$size] ?? '-' }}</td>
                                                @endforeach
                                                <td class="text-center fw-bold">{{ array_sum($sizeQtys) }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="{{ count($carton_sizes) + 2 }}" class="text-center">No carton qty / size data found.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold table-light">
                                                <td>Total</td>
                                                @foreach($carton_sizes as $size)
                                                <td class="text-center">
                                                    {{ array_sum(array_column($cartonPivot, $size)) }}
                                                </td>
                                                @endforeach
                                                <td class="text-center">{{ array_sum(array_map('array_sum', $cartonPivot)) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- AMENDMENT DETAILS --}}
                    @if($po->status == 1)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingAmendment">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseAmendment" aria-expanded="false"
                                aria-controls="collapseAmendment">
                                Amendment Details
                            </button>
                        </h2>
                        <div id="collapseAmendment" class="accordion-collapse collapse"
                            aria-labelledby="headingAmendment" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-warning" role="alert">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>This Purchase Order has been amended</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Amendment Information</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item">
                                                <strong>Job Number:</strong> {{ $po->po_job_num ?? 'N/A' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Amended By:</strong> {{ $po->amend->full_name ?? 'N/A' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Amended At:</strong>
                                                {{ $po->amended_at ? \Carbon\Carbon::parse($po->amended_at)->format('d-m-Y H:i:s') : 'N/A' }}
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Amendment Remarks</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item">
                                                <strong>Remarks:</strong> {{ $po->remarks ?? 'N/A' }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>