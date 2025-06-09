<div class="modal-dialog modal-xl">
    <div class="modal-content">
        @php
        $po = $data['po_details'];
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
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapsePoDetails" aria-expanded="true"
                                aria-controls="collapsePoDetails">
                                PO Details
                            </button>
                        </h2>
                        <div id="collapsePoDetails" class="accordion-collapse collapse show"
                            aria-labelledby="headingPoDetails" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>PO Information</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item">
                                                <strong>PO Number:</strong> {{ $po['PO Number'] ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>PO Date:</strong> {{ $po['PO Date'] ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Delivery Date:</strong> {{ $po['Goods Ready Date'] ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>GSTIN:</strong> {{ $po['GSTIN'] ?? '' }}
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-6">
                                        <h5>Delivery Address</h5>
                                        <ul class="list-group mb-3">
                                            @if(isset($po['Delivery Address']) && is_array($po['Delivery Address']))
                                            <li class="list-group-item">
                                                <strong>Address:</strong><br>
                                                @foreach($po['Delivery Address'] as $addressLine)
                                                {{ $addressLine }}<br>
                                                @endforeach
                                            </li>
                                            @else
                                            <li class="list-group-item">
                                                <strong>Address:</strong> {{ $po['Delivery Address'] ?? 'Not available' }}
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PO ITEMS TABLE --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingPoItems">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapsePoItems" aria-expanded="false"
                                aria-controls="collapsePoItems">
                                PO Items
                            </button>
                        </h2>
                        <div id="collapsePoItems" class="accordion-collapse collapse"
                            aria-labelledby="headingPoItems" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">
                                {{-- PO Items Table --}}
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>S.No</th>
                                                <th>HSN Code</th>
                                                <th>Article No</th>
                                                <th>Description</th>
                                                <th>Color</th>
                                                <th>Size</th>
                                                <th>QTY</th>
                                                <th>Unit Price</th>
                                                <th>Material Value</th>
                                                <th>GST %</th>
                                                <th>GST Amount</th>
                                                <th>Total Value</th>
                                                <th>Due Date</th>
                                                <th>MRP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                            $poItems = $data['po_items'] ?? [];
                                            $totalMaterialValue = 0;
                                            $totalGstAmount = 0;
                                            $totalValue = 0;
                                            @endphp

                                            @foreach($poItems as $index => $item)
                                            @php
                                            $materialValue = floatval(str_replace(',', '', $item['Material Value'] ?? 0));
                                            $gstAmount = floatval(str_replace(',', '', $item['IGST\nAmount'] ?? 0));
                                            $itemTotalValue = floatval(str_replace(',', '', $item['Total Value'] ?? 0));
                                            
                                            $totalMaterialValue += $materialValue;
                                            $totalGstAmount += $gstAmount;
                                            $totalValue += $itemTotalValue;
                                            @endphp

                                            <tr>
                                                <td>{{ $item['S.N\no'] ?? ($index + 1) }}</td>
                                                <td>{{ $item['HSN\nCode'] ?? '' }}</td>
                                                <td>{{ $item['Part No'] ?? '' }}</td>
                                                <td>{{ $item['Part Description'] ?? '' }}</td>
                                                <td>{{ $item['Col'] ?? '' }}</td>
                                                <td>{{ $item['Sz\nGr\np'] ?? '' }}</td>
                                                <td>{{ number_format(floatval(str_replace(',', '', $item['Qty'] ?? 0))) }}</td>
                                                <td>{{ number_format(floatval(str_replace(',', '', $item['Basic Cost'] ?? 0)), 2) }}</td>
                                                <td>{{ number_format($materialValue, 2) }}</td>
                                                <td>{{ $item['IGST\n%'] ?? 0 }}%</td>
                                                <td>{{ number_format($gstAmount, 2) }}</td>
                                                <td>{{ number_format($itemTotalValue, 2) }}</td>
                                                <td>{{ $item['Due Date'] ?? '' }}</td>
                                                <td>{{ number_format(floatval(str_replace(',', '', $item['MRP/UNIT'] ?? 0)), 2) }}</td>
                                            </tr>
                                            @endforeach

                                            <tr class="table-secondary">
                                                <td colspan="8" class="text-end"><strong>Total</strong></td>
                                                <td><strong>{{ number_format($totalMaterialValue, 2) }}</strong></td>
                                                <td></td>
                                                <td><strong>{{ number_format($totalGstAmount, 2) }}</strong></td>
                                                <td><strong>{{ number_format($totalValue, 2) }}</strong></td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Size Breakdown Table --}}
                                @if(isset($data['size_breakdown']) && count($data['size_breakdown']) > 0)
                                <div class="mt-4">
                                    <h5>Size Breakdown - Style {{ $data['style'] ?? '' }}</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Color</th>
                                                    <th>XS</th>
                                                    <th>S</th>
                                                    <th>M</th>
                                                    <th>L</th>
                                                    <th>XL</th>
                                                    <th>EL</th>
                                                    <th>TOTAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data['size_breakdown'] as $row)
                                                <tr>
                                                    @foreach($row as $cell)
                                                    <td>{{ $cell }}</td>
                                                    @endforeach
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>