<div class="modal-dialog modal-xl">
    <div class="modal-content">
        @php
        $po = $data['po_master'];
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
                                                <strong>Order Number:</strong> {{ $po->po_num ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Order Date:</strong> {{ $po->po_date ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Customer Name ID:</strong> {{ $po->vendor_customer_name ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Customer GSTIN:</strong> {{ $po->vendor_gst ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Customer Address:</strong><br>
                                                {{ $po->vendor_com_adr ?? 'Not available' }}
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-6">
                                        <h5>Delivery Address</h5>
                                        <ul class="list-group mb-3">
                                            @if(isset($po->vendor_del_adr) && is_array(json_decode($po->vendor_del_adr, true)))
                                            @php
                                            $delivery_address = json_decode($po->vendor_del_adr, true);
                                            @endphp
                                            <li class="list-group-item">
                                                <strong>Delivery Address:</strong><br>
                                                @foreach($delivery_address as $addressLine)
                                                {{ $addressLine }}<br>
                                                @endforeach
                                            </li>
                                            @else
                                            <li class="list-group-item">
                                                <strong>Delivery Address:</strong> {{ $po->vendor_del_adr ?? 'Not available' }}
                                            </li>
                                            @endif
                                            <li class="list-group-item">
                                                <strong>HSN Code:</strong> {{ $data['hsn_code'] ?? '' }}
                                            </li>
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
                                                <th>Article Number</th>
                                                <th>Gender</th>
                                                <th>Type</th>
                                                <th>Content</th>
                                                <th>HSN Code</th>
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
                                                <th>Total QTY</th>
                                                <th>Unit Price (INR)</th>
                                                <th>IGST %</th>
                                                <th>IGST Taxable Value</th>
                                                <th>Total Amount (INR)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                            $poItems = $data['po_items'] ?? collect();
                                            $totalQty = 0;
                                            $totalIgstValue = 0;
                                            $totalAmount = 0;
                                            $groupedItems = $poItems->groupBy('sno');
                                            @endphp

                                            @foreach($groupedItems as $sno => $items)
                                            @php
                                            $firstItem = $items->first();
                                            $groupQty = $items->sum('qty');
                                            $groupIgstValue = $items->sum('igst_taxable_value');
                                            $groupTotalAmount = $items->sum('total_amount');

                                            // Create size array for this group
                                            $sizeQty = [];
                                            foreach($items as $item) {
                                            $sizeQty[$item->size] = $item->qty;
                                            }

                                            $totalQty += $groupQty;
                                            $totalIgstValue += $groupIgstValue;
                                            $totalAmount += $groupTotalAmount;
                                            @endphp

                                            <tr>
                                                <td>{{ $sno }}</td>
                                                <td>{{ $firstItem->article_number ?? '' }}</td>
                                                <td>{{ $firstItem->gender ?? '' }}</td>
                                                <td>{{ $firstItem->type ?? '' }}</td>
                                                <td>{{ $firstItem->content ?? '' }}</td>
                                                <td>{{ $firstItem->hsn_code ?? '' }}</td>
                                                <td>{{ $firstItem->color ?? '' }}</td>
                                                <td>{{ $firstItem->color_code ?? '' }}</td>
                                                <td>{{ $firstItem->fi_dates ?? '' }}</td>
                                                <td>{{ isset($sizeQty['XS']) && $sizeQty['XS'] > 0 ? number_format($sizeQty['XS']) : '0' }}</td>
                                                <td>{{ isset($sizeQty['S']) && $sizeQty['S'] > 0 ? number_format($sizeQty['S']) : '0' }}</td>
                                                <td>{{ isset($sizeQty['M']) && $sizeQty['M'] > 0 ? number_format($sizeQty['M']) : '0' }}</td>
                                                <td>{{ isset($sizeQty['L']) && $sizeQty['L'] > 0 ? number_format($sizeQty['L']) : '0' }}</td>
                                                <td>{{ isset($sizeQty['XL']) && $sizeQty['XL'] > 0 ? number_format($sizeQty['XL']) : '0' }}</td>
                                                <td>{{ isset($sizeQty['XXL']) && $sizeQty['XXL'] > 0 ? number_format($sizeQty['XXL']) : '0' }}</td>
                                                <td>{{ isset($sizeQty['XXXL']) && $sizeQty['XXXL'] > 0 ? number_format($sizeQty['XXXL']) : '0' }}</td>
                                                <td>{{ number_format($groupQty) }}</td>
                                                <td>{{ number_format($firstItem->unit_price ?? 0, 2) }}</td>
                                                <td>{{ $firstItem->igst_per ?? '' }}%</td>
                                                <td>{{ number_format($groupIgstValue, 2) }}</td>
                                                <td>{{ number_format($groupTotalAmount, 2) }}</td>
                                            </tr>
                                            @endforeach

                                            @php
                                            // Calculate totals for each size column
                                            $sizeTotals = ['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0, 'XXL' => 0, 'XXXL' => 0];
                                            foreach($poItems as $item) {
                                            if(isset($sizeTotals[$item->size])) {
                                            $sizeTotals[$item->size] += $item->qty;
                                            }
                                            }
                                            @endphp

                                            <tr class="table-secondary">
                                                <td colspan="16" class="text-end"><strong>Total</strong></td>
                                                <td><strong>{{ number_format($totalQty) }}</strong></td>
                                                <td></td>
                                                <td></td>
                                                <td><strong>{{ number_format($totalIgstValue, 2) }}</strong></td>
                                                <td><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                                            </tr>
                                            <tr class="table-dark">
                                                <td colspan="20" class="text-end"><strong>Grand Total (Amount + IGST)</strong></td>
                                                <td><strong>{{ number_format($totalIgstValue + $totalAmount, 2) }}</strong></td>
                                            </tr>
                                        </tbody>
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