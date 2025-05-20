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
                                    <strong>Order No:</strong> {{ $data['po_details']['order_no'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Order Date:</strong> {{ $data['po_details']['order_date'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Customer Name:</strong> {{ $data['po_details']['customer_name'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Customer GSTIN:</strong> {{ $data['po_details']['customer_gstin'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Customer Address:</strong> <br>
                                    {{ $data['po_details']['customer_address'] ?? '' }}
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h5>Ship To</h5>
                            <ul class="list-group mb-3">
                                @if(isset($data['po_details']['ship_to_address']) && is_array($data['po_details']['ship_to_address']))
                                <li class="list-group-item">
                                    <strong>Delivery Address:</strong><br>
                                    @foreach($data['po_details']['ship_to_address'] as $addressLine)
                                    {{ $addressLine }}<br>
                                    @endforeach
                                </li>
                                @else
                                <li class="list-group-item">
                                    <strong>Delivery Address:</strong> {{ $data['po_details']['ship_to_address'] ?? 'Not available' }}
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
                    {{-- PO Items Table - Skechers specific columns --}}
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>S.No</th>
                                    <th>Style No.</th>
                                    <th>Gender</th>
                                    <th>Type</th>
                                    <th>Content</th>
                                    <th>Style Name</th>
                                    <th>Color</th>
                                    <th>Color Code</th>
                                    <th>FI Dates</th>
                                    <th>XS</th>
                                    <th>S</th>
                                    <th>M</th>
                                    <th>L</th>
                                    <th>XL</th>
                                    <th>XXL</th>
                                    <th>XXXL</th>
                                    <th>QTY</th>
                                    <th>Unit Price (INR)</th>
                                    <th>GST %</th>
                                    <th>GST Amount</th>
                                    <th>Total Amount (INR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $poItems = $data['po_items'] ?? [];
                                $totalQty = 0;
                                $totalGstAmount = 0;
                                $totalAmount = 0;
                                @endphp

                                @foreach($poItems as $index => $item)
                                @php
                                $slNo = $item['Sr. No.'] ?? $index + 1;
                                $styleNo = $item['Style No.'] ?? '';
                                $gender = $item['Gender'] ?? '';
                                $type = $item['Type'] ?? '';
                                $content = $item['Content'] ?? '';
                                $styleName = $item['STYLE NAME'] ?? '';
                                $color = $item['Color'] ?? '';
                                $colorCode = $item['Color Code'] ?? '';
                                $fiDates = $item['FI dates'] ?? '';
                                $xs = $item['XS'] ?? 0;
                                $s = $item['S'] ?? 0;
                                $m = $item['M'] ?? 0;
                                $l = $item['L'] ?? 0;
                                $xl = $item['XL'] ?? 0;
                                $xxl = $item['XXL'] ?? 0;
                                $xxxl = $item['XXXL'] ?? 0;
                                $qty = $item['QTY IN PCS'] ?? 0;
                                $unitPrice = $item['Unit Price (INR) - (b)'] ?? 0;
                                $gstPercent = $item['IGST'] ?? '';
                                $gstAmount = $item['Gst Total'] ?? 0; // Corrected key
                                $totalAmountItem = $item['Amount (INR) - (c = a x b)'] ?? 0;

                                $totalQty += intval(str_replace(',', '', $qty));
                                $totalGstAmount += floatval(str_replace(',', '', $gstAmount));
                                $totalAmount += floatval(str_replace(',', '', $totalAmountItem));
                                @endphp

                                <tr>
                                    <td>{{ $slNo }}</td>
                                    <td>{{ $styleNo }}</td>
                                    <td>{{ $gender }}</td>
                                    <td>{{ $type }}</td>
                                    <td>{{ $content }}</td>
                                    <td>{{ $styleName }}</td>
                                    <td>{{ $color }}</td>
                                    <td>{{ $colorCode }}</td>
                                    <td>{{ $fiDates }}</td>
                                    <td>{{ $xs }}</td>
                                    <td>{{ $s }}</td>
                                    <td>{{ $m }}</td>
                                    <td>{{ $l }}</td>
                                    <td>{{ $xl }}</td>
                                    <td>{{ $xxl }}</td>
                                    <td>{{ $xxxl }}</td>
                                    <td>{{ number_format(intval(str_replace(',', '', $qty))) }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $unitPrice)), 2) }}</td>
                                    <td>{{ $gstPercent }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $gstAmount)), 2) }}</td>
                                    <td>{{ number_format(floatval(str_replace(',', '', $totalAmountItem)), 2) }}</td>
                                </tr>
                                @endforeach

                                <tr class="table-secondary">
                                    <td colspan="16" class="text-end"><strong>Total</strong></td>
                                    <td><strong>{{ number_format($totalQty) }}</strong></td>
                                    <td></td>
                                    <td></td>
                                    <td><strong>{{ number_format($totalGstAmount, 2) }}</strong></td>
                                    <td><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
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
    <input type="hidden" name="po_data" class="po_data" value="{{ json_encode($data) }}">
    <button type="button" class="btn btn-success btn-block w-100" id="saveButton" disabled>
        Verify & Save PO
    </button>
</div>