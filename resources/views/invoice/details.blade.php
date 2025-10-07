<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header text-dark">
            <h5 class="modal-title">
                <i class="fas fa-file-invoice me-2"></i>
                Invoice Details - #{{ $invoice->ref_no }}
            </h5>
            <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <div class="row">
                <!-- Invoice Information -->
                <div class="col-md-6 mb-4">
                    <h6 class="border-bottom pb-2"><i class="fas fa-info-circle me-2"></i>Invoice Information</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="fw-bold">Invoice No:</td>
                            <td>{{ $invoiceSummary['ref_no'] }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Invoice Date:</td>
                            <td>{{ \Carbon\Carbon::parse($invoiceSummary['inv_date'])->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">PO Number:</td>
                            <td>{{ $invoiceSummary['po_num'] }}</td>
                        </tr>
                        @if($invoiceSummary['vendor']->id == 7 && !empty($invoiceSummary['da_no']))
                        <tr>
                            <td class="fw-bold">DA Number:</td>
                            <td>{{ $invoiceSummary['da_no'] }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="fw-bold">Vendor:</td>
                            <td>{{ $invoiceSummary['vendor']->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Status:</td>
                            <td>
                                @php
                                    $statusName = $invoice->invoiceStatus->name ?? 'N/A';
                                    $badgeClass = match(strtolower(trim($statusName))) {
                                        'invoiced' => 'bg-info text-white',
                                        'in transit' => 'bg-warning text-dark',
                                        'grn pending' => 'bg-warning text-dark',
                                        'grn done' => 'bg-primary text-white',
                                        'payment pending' => 'bg-orange text-white',
                                        'payment received' => 'bg-success text-white',
                                        'cancelled' => 'bg-danger text-white',
                                        default => 'bg-light text-dark'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $statusName }}</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Transport Details -->
                <div class="col-md-6 mb-4">
                    <h6 class="border-bottom pb-2"><i class="fas fa-truck me-2"></i>Transport Details</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="fw-bold">Mode:</td>
                            <td>{{ $invoiceSummary['transporter_details']['mode_of_transport'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Transporter:</td>
                            <td>{{ $invoiceSummary['transporter_details']['transport_name_display'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Vehicle No:</td>
                            <td>{{ $invoiceSummary['transporter_details']['transport_vehicle_no'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Distance:</td>
                            <td>{{ $invoiceSummary['transporter_details']['transport_distance'] ?? 'N/A' }} km</td>
                        </tr>
                    </table>
                </div>

                <!-- Bill To -->
                <div class="col-md-6 mb-4">
                    <h6 class="border-bottom pb-2"><i class="fas fa-user me-2"></i>Bill To Details</h6>
                    <div>
                        <p class="mb-1"><strong>{{ $invoiceSummary['bill_to_details']['billed_legal_name'] ?? 'N/A' }}</strong></p>
                        <p class="mb-1">{{ $invoiceSummary['bill_to_details']['billed_address_1'] ?? '' }}</p>
                        <p class="mb-1">{{ $invoiceSummary['bill_to_details']['billed_address_2'] ?? '' }}</p>
                        <p class="mb-1">{{ $invoiceSummary['bill_to_details']['billed_city'] ?? '' }}-{{ $invoiceSummary['bill_to_details']['billed_pincode'] ?? '' }}</p>
                        <p class="mb-1">STATE: {{ $invoiceSummary['bill_to_details']['billed_state_name'] ?? '' }}</p>
                        <p class="mb-1">State Code: {{ $invoiceSummary['bill_to_details']['billed_state_code'] ?? '' }}</p>
                        <p class="mb-0">GSTIN: {{ $invoiceSummary['bill_to_details']['billed_gst_no'] ?? '' }}</p>
                    </div>
                </div>

                <!-- Ship To -->
                <div class="col-md-6 mb-4">
                    <h6 class="border-bottom pb-2"><i class="fas fa-shipping-fast me-2"></i>Ship To Details</h6>
                    <div>
                        <p class="mb-1"><strong>{{ $invoiceSummary['ship_to_details']['shipped_legal_name'] ?? 'N/A' }}</strong></p>
                        <p class="mb-1">{{ $invoiceSummary['ship_to_details']['shipped_address_1'] ?? '' }}</p>
                        <p class="mb-1">{{ $invoiceSummary['ship_to_details']['shipped_address_2'] ?? '' }}</p>
                        <p class="mb-1">{{ $invoiceSummary['ship_to_details']['shipped_city'] ?? '' }}-{{ $invoiceSummary['ship_to_details']['shipped_pincode'] ?? '' }}</p>
                        <p class="mb-1">STATE: {{ $invoiceSummary['ship_to_details']['shipped_state_name'] ?? '' }}</p>
                        <p class="mb-1">State Code: {{ $invoiceSummary['ship_to_details']['shipped_state_code'] ?? '' }}</p>
                        <p class="mb-0">GSTIN: {{ $invoiceSummary['ship_to_details']['shipped_gst_no'] ?? '' }}</p>
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="col-12">
                    <h6 class="border-bottom pb-2"><i class="fas fa-list me-2"></i>Invoice Items Details</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">SI No</th>
                                    <th class="text-center">Description</th>
                                    <th class="text-center">HSN</th>
                                    <th class="text-center">Style No</th>
                                    <th class="text-center">Color</th>
                                    <th class="text-center">Size</th>
                                    <th class="text-center">Cartons</th>
                                    <th class="text-center">UOM</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Rate</th>
                                    <th class="text-center">Amount</th>
                                    <th class="text-center">Discount</th>
                                    <th class="text-center">Taxable Value</th>
                                    <th class="text-center">IGST Rate</th>
                                    <th class="text-center">IGST Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalQty = 0;
                                    $totalAmount = 0;
                                    $totalDiscount = 0;
                                    $totalTaxable = 0;
                                    $totalTaxAmount = 0;
                                @endphp
                                @foreach($invoiceSummary['detailed_items'] as $i => $item)
                                @php
                                    $totalQty += $item['qty'];
                                    $totalAmount += $item['amount'];
                                    $totalDiscount += $item['discount'];
                                    $totalTaxable += $item['taxable_value'];
                                    $totalTaxAmount += $item['igst_amount'];
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td>{{ $item['description'] }}</td>
                                    <td class="text-center">{{ $item['hsn_code'] }}</td>
                                    <td class="text-center">{{ $item['style'] }}</td>
                                    <td class="text-center">{{ $item['colors'] }}</td>
                                    <td class="text-center">{{ $item['size'] }}</td>
                                    @if ($i === 0)
                                    <td class="text-center" rowspan="{{ count($invoiceSummary['detailed_items']) }}">
                                        <strong>{{ $invoiceSummary['total_cartons'] }}</strong>
                                    </td>
                                    @endif
                                    <td class="text-center">{{ $item['unit'] }}</td>
                                    <td class="text-right">{{ number_format($item['qty']) }}</td>
                                    <td class="text-right">₹{{ number_format($item['rate'], 2) }}</td>
                                    <td class="text-right">₹{{ number_format($item['amount'], 2) }}</td>
                                    <td class="text-right">₹{{ number_format($item['discount'], 2) }}</td>
                                    <td class="text-right">₹{{ number_format($item['taxable_value'], 2) }}</td>
                                    <td class="text-center">{{ number_format($item['igst_rate'], 2) }}%</td>
                                    <td class="text-right">₹{{ number_format($item['igst_amount'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="text-right"><strong>Total</strong></td>
                                    <td></td>
                                    <td class="text-right"><strong>{{ number_format($totalQty) }}</strong></td>
                                    <td></td>
                                    <td class="text-right"><strong>₹{{ number_format($totalAmount, 2) }}</strong></td>
                                    <td class="text-right"><strong>₹{{ number_format($totalDiscount, 2) }}</strong></td>
                                    <td class="text-right"><strong>₹{{ number_format($totalTaxable, 2) }}</strong></td>
                                    <td></td>
                                    <td class="text-right"><strong>₹{{ number_format($totalTaxAmount, 2) }}</strong></td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="14" class="text-right"><strong>Final Amount</strong></td>
                                    <td class="text-right"><strong>₹{{ number_format($invoiceSummary['final_amount'], 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <a href="{{ route('generateInvoice', ['id' => $invoice->id]) }}" target="_blank" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Invoice
            </a>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
