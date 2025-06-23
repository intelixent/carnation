<div class="modal-dialog modal-xl">
    <div class="modal-content">
        @php
        $po = $data['po_master'];
        @endphp
        <div class="modal-header">
            <h5 class="modal-title">Purchase Order Details - #{{ $data['po_master']->po_ref_num ?? '' }}</h5>
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
                                                <strong>PO Number:</strong> {{ $data['po_master']->po_num ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Release Date:</strong> {{ $data['po_master']->po_date ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>EHD Date:</strong> {{ $data['po_master']->goods_ready_date ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Customer Address:</strong><br>
                                                <div style="white-space: pre-line;">{{ $data['po_master']->vendor_com_adr ?? 'No customer address found' }}</div>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-6">
                                        <h5>Delivery Information</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item">
                                                <strong>Delivery Address:</strong><br>
                                                <div style="white-space: pre-line;">{{ $data['po_master']->vendor_del_adr ?? 'No delivery address found' }}</div>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>HSN Code:</strong><br>
                                                <div style="white-space: pre-line;">{{ $data['hsn_code'] ?? 'No HSN code found' }}</div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ARTICLE INFO --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingArticleInfo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseArticleInfo" aria-expanded="false"
                                aria-controls="collapseArticleInfo">
                                Article Info
                            </button>
                        </h2>
                        <div id="collapseArticleInfo" class="accordion-collapse collapse"
                            aria-labelledby="headingArticleInfo" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">
                                @php
                                $article_info = json_decode($data['po_master']->article_info, true);
                                @endphp
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td><strong>Article Number:</strong></td>
                                            <td>{{ $article_info['article_number'] ?? '' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Style Description:</strong></td>
                                            <td>{{ $article_info['style_description'] ?? '' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Color:</strong></td>
                                            <td>{{ $article_info['color'] ?? '' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer PO NO:</strong></td>
                                            <td>{{ $article_info['customer_po_no'] ?? '' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
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
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Size</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Pack Factor</th>
                                                <th>SKU/Line No</th>
                                                <th>Incoterm</th>
                                                <th>Named Place</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                            $totalQty = 0;
                                            $totalUnitPrice = 0;
                                            @endphp

                                            @if($data['po_items']->count() > 0)
                                            @foreach($data['po_items'] as $item)
                                            @php
                                            $totalQty += $item->qty ?? 0;
                                            $totalUnitPrice += $item->unit_price ?? 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $item->size ?? '' }}</td>
                                                <td>{{ $item->qty ?? '' }}</td>
                                                <td>{{ number_format($item->unit_price, 2) ?? '' }}</td>
                                                <td>{{ $item->pack_factor ?? '' }}</td>
                                                <td>{{ $item->sku_line_no ?? '' }}</td>
                                                <td>{{ $item->incoterm ?? '' }}</td>
                                                <td>{{ $item->named_place ?? '' }}</td>
                                            </tr>
                                            @endforeach

                                            {{-- Total Row --}}
                                            <tr class="fw-bold table-secondary">
                                                <td class="text-end">Total</td>
                                                <td>{{ $totalQty }}</td>
                                                <td>{{ number_format($totalUnitPrice, 2) }}</td>
                                                <td colspan="4"></td>
                                            </tr>
                                            @else
                                            <tr>
                                                <td colspan="7" class="text-center">No items found</td>
                                            </tr>
                                            @endif
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