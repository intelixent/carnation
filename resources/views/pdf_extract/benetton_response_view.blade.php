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
                                    <strong>Order No:</strong> {{ $data['order_no'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Order Date:</strong> {{ $data['order_date'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Delivery Date:</strong> {{ $data['delivery_date'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Season:</strong> {{ $data['season'] ?? '' }}
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h5>Ship To</h5>
                            <ul class="list-group mb-3">
                                @if(isset($data['ship_to_address']) && is_array($data['ship_to_address']))
                                <li class="list-group-item">
                                    <strong>Address:</strong><br>
                                    @foreach($data['ship_to_address'] as $addressLine)
                                    {{ $addressLine }}<br>
                                    @endforeach
                                </li>
                                @else
                                <li class="list-group-item">
                                    <strong>Address:</strong> {{ $data['ship_to_address'] ?? 'Not available' }}
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
                            <thead class="table-dark">
                                <tr>
                                    <th>S.No</th>
                                    <th>HSN Code</th>
                                    <th>Part No</th>
                                    <th>Description</th>
                                    <th>Color</th>
                                    <th>QTY</th>
                                    <th>Basic Cost</th>
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
                                $slNo = $item['S.N o'] ?? $item['sl_no'] ?? ($index + 1);
                                $hsnCode = $item['HSN Code'] ?? $item['hsn_code'] ?? '';
                                $partNo = $item['Part No'] ?? $item['part_no'] ?? '';
                                $partDesc = $item['Part Description'] ?? $item['description'] ?? '';
                                $color = $item['Col'] ?? $item['color'] ?? '';
                                $qty = $item['Qty'] ?? $item['quantity'] ?? 0;
                                $basicCost = $item['Basic Cost'] ?? $item['basic_cost'] ?? 0;
                                $materialValue = $item['Material Value'] ?? $item['material_value'] ?? 0;
                                $gstPercent = $item['IGST %'] ?? $item['gst_percent'] ?? 0;
                                $gstAmount = $item['IGST Amount'] ?? $item['gst_amount'] ?? 0;
                                $itemTotalValue = $item['Total Value'] ?? $item['total_value'] ?? 0;
                                $dueDate = $item['Due Date'] ?? $item['due_date'] ?? '';
                                $mrp = $item['MRP/UNIT'] ?? $item['mrp'] ?? 0;

                                $totalMaterialValue += floatval(str_replace(',', '', $materialValue));
                                $totalGstAmount += floatval(str_replace(',', '', $gstAmount));
                                $totalValue += floatval(str_replace(',', '', $itemTotalValue));
                                @endphp

                                <tr>
                                    <td>{{ $slNo }}</td>
                                    <td>{{ $hsnCode }}</td>
                                    <td>{{ $partNo }}</td>
                                    <td>{{ $partDesc }}</td>
                                    <td>{{ $color }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $qty))) }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $basicCost)), 2) }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $materialValue)), 2) }}</td>
                                    <td>{{ $gstPercent }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $gstAmount)), 2) }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $itemTotalValue)), 2) }}</td>
                                    <td>{{ $dueDate }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $mrp)), 2) }}</td>
                                </tr>
                                @endforeach

                                <tr class="table-secondary">
                                    <td colspan="7" class="text-end"><strong>Total</strong></td>
                                    <td><strong>{{ number_format($totalMaterialValue, 2) }}</strong></td>
                                    <td></td>
                                    <td><strong>{{ number_format($totalGstAmount, 2) }}</strong></td>
                                    <td><strong>{{ number_format($totalValue, 2) }}</strong></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Size Tables --}}
                    @if(isset($data['raw_tables']))
                    @php
                    $sizeTable = null;
                    // Try to find the size table in the raw_tables array
                    foreach($data['raw_tables'] as $table) {
                    if(count($table) > 0 && isset($table[0][0]) && $table[0][0] == 'COL/SIZ') {
                    $sizeTable = $table;
                    break;
                    }
                    }
                    @endphp

                    @if($sizeTable)
                    <div class="mt-4">
                        <h5>Size Breakdown - Style {{ $data['style'] ?? '' }}</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Color</th>
                                        @foreach(explode(' ', $sizeTable[0][1] ?? '') as $size)
                                        <th>{{ $size }}</th>
                                        @endforeach
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(array_slice($sizeTable, 1) as $row)
                                    @if(!empty($row[0]) && $row[0] != 'Terms & Conditions:')
                                    <tr>
                                        <td>{{ $row[0] }}</td>
                                        @foreach(explode(' ', $row[1] ?? '') as $qty)
                                        <td>{{ $qty }}</td>
                                        @endforeach
                                        <td>{{ $row[2] ?? '' }}</td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @elseif(isset($data['size_tables']) && count($data['size_tables']) > 0)
                    @foreach($data['size_tables'] as $sizeTable)
                    <div class="mt-4">
                        <h5>Size Breakdown</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Color</th>
                                        @if(isset($sizeTable['headers']))
                                        @foreach($sizeTable['headers'] as $size)
                                        <th>{{ $size }}</th>
                                        @endforeach
                                        @endif
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($sizeTable['rows']))
                                    @foreach($sizeTable['rows'] as $row)
                                    <tr>
                                        <td>{{ $row[0] }}</td>
                                        @foreach(explode(' ', $row[1]) as $qty)
                                        <td>{{ $qty }}</td>
                                        @endforeach
                                        <td>{{ $row[2] }}</td>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                    @endif
                    @endif
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
    <input type="hidden" name="po_data" class="po_data"
        value="{{ json_encode($data) }}">
    <button type="button" class="btn btn-success btn-block w-100" id="saveButton" disabled>
        Verify & Save PO
    </button>
</div>