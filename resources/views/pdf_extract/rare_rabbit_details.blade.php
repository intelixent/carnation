<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Purchase Order Details - #{{ $data['po_master']->po_ref_num ?? '' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="accordion" id="rrDetailsAccordion">
                    {{-- PO DETAILS --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="rrHeadingPoDetails">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#rrCollapsePoDetails" aria-expanded="true"
                                aria-controls="rrCollapsePoDetails">
                                PO Details
                            </button>
                        </h2>
                        <div id="rrCollapsePoDetails" class="accordion-collapse collapse show"
                            aria-labelledby="rrHeadingPoDetails" data-bs-parent="#rrDetailsAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h5>PO Information</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item"><strong>PO No:</strong> {{ $data['po_master']->po_num ?? '' }}</li>
                                            <li class="list-group-item"><strong>Category:</strong> {{ $data['article_info']['category'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>PO Date:</strong> {{ $data['po_master']->po_date ?? '' }}</li>
                                            <li class="list-group-item"><strong>Delivery Date:</strong> {{ $data['po_master']->goods_ready_date ?? '' }}</li>
                                            <li class="list-group-item"><strong>Channel:</strong> {{ $data['article_info']['channel'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Colors:</strong> {{ $data['po_master']->colors ?? '' }}</li>
                                            <li class="list-group-item"><strong>Currency:</strong> {{ $data['article_info']['currency'] ?? '' }}</li>
                                        </ul>
                                    </div>

                                    <div class="col-md-4">
                                        <h5>Ship To (Warehouse)</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item"><strong>Name:</strong> {{ $data['article_info']['warehouse_name'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Address:</strong> {{ $data['po_master']->vendor_del_adr ?? '' }}</li>
                                            <li class="list-group-item"><strong>CIN:</strong> {{ $data['po_master']->vendor_cin ?? '' }}</li>
                                            <li class="list-group-item"><strong>GSTIN:</strong> {{ $data['po_master']->vendor_gst ?? '' }}</li>
                                            <li class="list-group-item"><strong>GST State:</strong> {{ $data['article_info']['warehouse_gst_state'] ?? '' }}</li>
                                        </ul>
                                    </div>

                                    <div class="col-md-4">
                                        <h5>Vendor</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item"><strong>Name:</strong> {{ $data['article_info']['vendor_name'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Vendor ID:</strong> {{ $data['article_info']['vendor_id_code'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Address:</strong> {{ $data['po_master']->vendor_com_adr ?? '' }}</li>
                                            <li class="list-group-item"><strong>GST State:</strong> {{ $data['article_info']['vendor_gst_state'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Email:</strong> {{ $data['article_info']['email'] ?? '' }}</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Totals</h5>
                                        <ul class="list-group mb-3 list-group-horizontal flex-wrap">
                                            <li class="list-group-item"><strong>Total Basic:</strong> {{ $data['article_info']['total_basic_amount'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>IGST %:</strong> {{ $data['article_info']['igst_pct'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>IGST Amount:</strong> {{ $data['article_info']['igst_amount'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Net Amount:</strong> {{ $data['article_info']['net_amount'] ?? '' }}</li>
                                        </ul>
                                        <p class="mb-0"><strong>Amount in Words:</strong> {{ $data['article_info']['amount_in_words'] ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PO ITEMS --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="rrHeadingPoItems">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#rrCollapsePoItems" aria-expanded="false"
                                aria-controls="rrCollapsePoItems">
                                PO Items ({{ count($data['po_items']) }})
                            </button>
                        </h2>
                        <div id="rrCollapsePoItems" class="accordion-collapse collapse"
                            aria-labelledby="rrHeadingPoItems" data-bs-parent="#rrDetailsAccordion">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>S.No</th>
                                                <th>EAN</th>
                                                <th>Content</th>
                                                <th>Size</th>
                                                <th>Qty</th>
                                                <th>UOM</th>
                                                <th>Unit Price</th>
                                                <th>Amount</th>
                                                <th>HSN</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($data['po_items'] as $item)
                                            <tr>
                                                <td>{{ $item->sno }}</td>
                                                <td>{{ $item->article_number }}</td>
                                                <td>{{ $item->content }}</td>
                                                <td>{{ $item->size }}</td>
                                                <td>{{ $item->qty }}</td>
                                                <td>{{ $item->uom }}</td>
                                                <td>{{ $item->unit_price }}</td>
                                                <td>{{ $item->total_amount }}</td>
                                                <td>{{ $item->hsn_code }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="9" class="text-center">No items found</td>
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
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>