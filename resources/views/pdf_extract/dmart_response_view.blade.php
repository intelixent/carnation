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
                                <li class="list-group-item"><strong>PO Number:</strong> {{ $data['po_number'] ?? '' }}</li>
                                <li class="list-group-item"><strong>PO Date:</strong> {{ $data['po_date'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Expected Delivery Date:</strong> {{ $data['exp_delivery_dt'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Total Qty:</strong> {{ $data['total_qty'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Total Boxes:</strong> {{ $data['total_boxes'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Total Value:</strong> {{ $data['total_value'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Amount in Words:</strong> {{ $data['amount_in_words'] ?? '' }}</li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h5>Buyer (Ship/Bill To)</h5>
                            <ul class="list-group mb-3">
                                <li class="list-group-item"><strong>Name:</strong> {{ $data['buyer_name'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Address:</strong> {{ $data['buyer_address'] ?? '' }}</li>
                                <li class="list-group-item"><strong>CIN:</strong> {{ $data['buyer_cin'] ?? '' }}</li>
                                <li class="list-group-item"><strong>GSTIN:</strong> {{ $data['buyer_gstin'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Attn:</strong> {{ $data['buyer_attn'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Email:</strong> {{ $data['buyer_email'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Buyer:</strong> {{ $data['buyer_buyer'] ?? '' }}</li>
                            </ul>

                            <h5>Vendor</h5>
                            <ul class="list-group mb-3">
                                <li class="list-group-item"><strong>Name:</strong> {{ $data['vendor_name'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Address:</strong> {{ $data['vendor_address'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Phone:</strong> {{ $data['vendor_phone'] ?? '' }}</li>
                                <li class="list-group-item"><strong>Email:</strong> {{ $data['vendor_email'] ?? '' }}</li>
                                <li class="list-group-item"><strong>GSTIN:</strong> {{ $data['vendor_gstin'] ?? '' }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PO ITEMS TABLE (read-only, as extracted from the PDF) --}}
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
                                @foreach($data['po_items'] as $item)
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
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="8">Total</td>
                                    <td>{{ $data['total_qty'] ?? '' }}</td>
                                    <td colspan="5">{{ $data['total_value'] ?? '' }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header" id="headingCartonQty">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCartonQty" aria-expanded="false" aria-controls="collapseCartonQty">
                    Carton Qty / Size Breakdown
                </button>
            </h2>
            <div id="collapseCartonQty" class="accordion-collapse collapse" aria-labelledby="headingCartonQty" data-bs-parent="#pdfAccordion">
                <div class="accordion-body">

                    @if(empty($sizes))
                    <div class="alert alert-warning">
                        No sizes found in the size chart for this vendor. Please configure the vendor's size chart first.
                    </div>
                    @endif

                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-success" id="addColorRowBtn">
                            <i class="fas fa-plus"></i> Add Color
                        </button>
                    </div>

                    {{--
                        data-total-qty carries the PO-level Total Qty extracted from the PDF (pieces),
                        used purely on the frontend to compute "Total Cartons" = Total Qty / Case Lot,
                        the same way data-sizes carries the size-chart columns.
                    --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle" id="cartonQtyTable" data-sizes='@json($sizes ?? [])' data-total-qty="{{ str_replace(',', '', $data['total_qty'] ?? 0) }}">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width:160px;">Color</th>
                                    @foreach($sizes as $size)
                                    <th class="text-center">{{ $size }}</th>
                                    @endforeach
                                    <th class="text-center">Row Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cartonQtyBody">
                                {{-- Color rows are appended here by cloning #cartonRowTemplate below (see add.blade.php) --}}
                            </tbody>
                            <tfoot>
                                <tr id="cartonQtyTotalsRow" class="fw-bold table-light">
                                    <td>Total</td>
                                    @foreach($sizes as $size)
                                    <td class="text-center total-size-cell" data-size="{{ $size }}">0</td>
                                    @endforeach
                                    <td class="text-center" id="grandTotalQty">0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <table style="display:none;">
                        <tbody>
                            <tr id="cartonRowTemplate">
                                <td>
                                    <input type="text" class="form-control form-control-sm carton-color-input" placeholder="Color">
                                </td>
                                @foreach($sizes as $size)
                                <td>
                                    <input type="number" min="0" class="form-control form-control-sm carton-qty-input" data-size="{{ $size }}" value="0">
                                </td>
                                @endforeach
                                <td class="text-center row-total">0</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-color-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label"><strong>Case Lot</strong></label>
                            <input type="number" step="0.01" class="form-control" id="caseLotInput" value="{{ $totalCaseLot ?? 0 }}">
                            <small class="text-muted">Auto-filled from the PDF, you can change it.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong>Color Count</strong></label>
                            <input type="text" class="form-control" id="colorCountDisplay" value="0" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong>Ratio (Case Lot &divide; Color Count)</strong></label>
                            <input type="text" class="form-control" id="ratioDisplay" value="0" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong>Total Cartons (Total Qty &divide; Case Lot)</strong></label>
                            <input type="text" class="form-control" id="totalCartonsDisplay" value="0" readonly>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="fixed-bottom p-3 bg-white border-top">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="verifyCheck">
        <label class="form-check-label" for="verifyCheck">
            I accept and verify PO.
        </label>
    </div>

    <input type="hidden" name="po_data" class="po_data" value='@json($data)' />
    <input type="hidden" name="carton_qty_sizes" class="carton_qty_sizes" value="" />
    <input type="hidden" name="vendor_name" id="vendor_name" value="D-Mart">

    <button type="button" class="btn btn-success btn-block w-100" id="saveButton" disabled>Verify & Save PO</button>
</div>