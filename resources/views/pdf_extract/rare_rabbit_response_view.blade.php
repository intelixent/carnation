<div class="row">
    <ul class="nav nav-tabs" id="rareRabbitPoTabs" role="tablist">
        @foreach($data['pos'] as $index => $po)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $index === 0 ? 'active' : '' }}"
                id="rr-tab-{{ $index }}" data-bs-toggle="tab"
                data-bs-target="#rr-pane-{{ $index }}" type="button" role="tab"
                aria-controls="rr-pane-{{ $index }}"
                aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                {{ $po['order_no'] ?? ('PO ' . ($index + 1)) }}
                <span class="badge bg-secondary ms-1">{{ count($po['po_items'] ?? []) }}</span>
            </button>
        </li>
        @endforeach
    </ul>

    <div class="tab-content border border-top-0 p-3" id="rareRabbitPoTabsContent">
        @foreach($data['pos'] as $index => $po)
        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
            id="rr-pane-{{ $index }}" role="tabpanel" aria-labelledby="rr-tab-{{ $index }}">

            <div class="accordion" id="rrAccordion{{ $index }}">
                {{-- PO DETAILS --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="rrHeadingDetails{{ $index }}">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                            data-bs-target="#rrCollapseDetails{{ $index }}" aria-expanded="true"
                            aria-controls="rrCollapseDetails{{ $index }}">
                            PO Details
                        </button>
                    </h2>
                    <div id="rrCollapseDetails{{ $index }}" class="accordion-collapse collapse show"
                        aria-labelledby="rrHeadingDetails{{ $index }}" data-bs-parent="#rrAccordion{{ $index }}">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h5>PO Information</h5>
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item"><strong>Order No:</strong> {{ $po['order_no'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Category:</strong> {{ $po['category'] ?? '' }}</li> 
                                        <li class="list-group-item"><strong>Order Date:</strong> {{ $po['order_date'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Channel:</strong> {{ $po['channel'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Delivery Date:</strong> {{ $po['delivery_date'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Payment Terms (Days):</strong> {{ $po['payment_terms_days'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Currency:</strong> {{ $po['currency'] ?? '' }}</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h5>Ship To (Warehouse)</h5>
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item"><strong>Name:</strong> {{ $po['warehouse_name'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Address:</strong> {{ $po['warehouse_address'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>CIN:</strong> {{ $po['cin'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>GSTIN:</strong> {{ $po['buyer_gstin'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>GST State:</strong> {{ $po['warehouse_gst_state'] ?? '' }}</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h5>Vendor</h5>
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item"><strong>Name:</strong> {{ $po['vendor_name'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Vendor ID:</strong> {{ $po['vendor_id'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Address:</strong> {{ $po['vendor_address'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>GSTIN:</strong> {{ $po['vendor_gstin'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>GST State:</strong> {{ $po['vendor_gst_state'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Email:</strong> {{ $po['email'] ?? '' }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Totals</h5>
                                    <ul class="list-group mb-3 list-group-horizontal flex-wrap">
                                        <li class="list-group-item"><strong>Total Qty:</strong> {{ $po['total_qty'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Total Basic:</strong> {{ $po['total_basic_amount'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Other Charges:</strong> {{ $po['other_charges'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Discount:</strong> {{ $po['discount_amount'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Round Off:</strong> {{ $po['round_off'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>IGST %:</strong> {{ $po['igst_pct'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>IGST Amount:</strong> {{ $po['igst_amount'] ?? '' }}</li>
                                        <li class="list-group-item"><strong>Net Amount:</strong> {{ $po['net_amount'] ?? '' }}</li>
                                    </ul>
                                    <p class="mb-0"><strong>Amount in Words:</strong> {{ $po['amount_in_words'] ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PO ITEMS --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="rrHeadingItems{{ $index }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#rrCollapseItems{{ $index }}" aria-expanded="false"
                            aria-controls="rrCollapseItems{{ $index }}">
                            PO Items ({{ count($po['po_items'] ?? []) }})
                        </button>
                    </h2>
                    <div id="rrCollapseItems{{ $index }}" class="accordion-collapse collapse"
                        aria-labelledby="rrHeadingItems{{ $index }}" data-bs-parent="#rrAccordion{{ $index }}">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>S.No</th>
                                            <th>EAN</th>
                                            <th>Description</th>
                                            <th>Size</th>
                                            <th>Season</th>
                                            <th>HSN</th>
                                            <th>Rate</th>
                                            <th>Qty</th>
                                            <th>UOM</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($po['po_items'] ?? [] as $itemIndex => $item)
                                        <tr>
                                            <td>{{ $itemIndex + 1 }}</td>
                                            <td>{{ $item['ean'] ?? '' }}</td>
                                            <td>{{ $item['description'] ?? '' }}</td>
                                            <td>{{ $item['size'] ?? '' }}</td>
                                            <td>{{ $item['season'] ?? '' }}</td>
                                            <td>{{ $item['hsn'] ?? '' }}</td>
                                            <td>{{ $item['rate'] ?? '' }}</td>
                                            <td>{{ $item['quantity'] ?? '' }}</td>
                                            <td>{{ $item['uom'] ?? '' }}</td>
                                            <td>{{ $item['amount'] ?? '' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="10" class="text-center">No items found on this PO</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="fixed-bottom p-3 bg-white border-top">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="verifyCheck">
        <label class="form-check-label" for="verifyCheck">
            I accept and verify all {{ count($data['pos']) }} Purchase Order(s).
        </label>
    </div>
    {{--
        Shares the .po_data class so the existing generic save handler in
        add.blade.php (which reads $(".po_data").val()) works unchanged.
        The value here is a JSON ARRAY (one object per PO), unlike every
        other vendor's single-object payload - the backend detects the
        array shape for vendor_id "9" and loops accordingly.
    --}}
    <input type="hidden" name="po_data" class="po_data rare_rabbit_po_data" value="{{ json_encode($data['pos']) }}">
    <input type="hidden" name="vendor_name" id="vendor_name" value="Rare Rabbit">
    <button type="button" class="btn btn-success btn-block w-100" id="saveButton" disabled>
        Verify &amp; Save {{ count($data['pos']) }} PO(s)
    </button>
</div>