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
                                    <strong>PO Number:</strong> {{ $data['po_details']['po_number'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Release Date:</strong> {{ $data['po_details']['po_release_date'] ?? '' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>EHD Date:</strong> {{ $data['po_details']['po_ehd'] ?? '' }}
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h5>Customer Information</h5>
                            <ul class="list-group mb-3">
                                <li class="list-group-item">
                                    <strong>Address:</strong>
                                    {{ $data['po_details']['customer_address'] ?? '' }}
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
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td><strong>Article Number:</strong></td>
                                <td>{{ $data['article_info']['article_number'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Style Description:</strong></td>
                                <td>{{ $data['article_info']['style_description'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Color:</strong></td>
                                <td>{{ $data['article_info']['color'] ?? '' }}</td>
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
                                @foreach($data['po_items'] as $item)
                                <tr>
                                    <td>{{ $item['size'] ?? '' }}</td>
                                    <td>{{ $item['quantity'] ?? '' }}</td>
                                    <td>{{ $item['unit_price'] ?? '' }}</td>
                                    <td>{{ $item['pack_factor'] ?? '' }}</td>
                                    <td>{{ $item['sku_line_no'] ?? '' }}</td>
                                    <td>{{ $item['incoterm'] ?? '' }}</td>
                                    <td>{{ $item['named_place'] ?? '' }}</td>
                                </tr>
                                @endforeach
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
    <input type="hidden" name="po_details" class="po_details"
        value="{{ json_encode($data['po_details']) }}">
    <input type="hidden" name="article_details" class="article_details"
        value="{{ json_encode($data['article_info']) }}">
    <input type="hidden" name="po_items" class="po_items"
        value="{{ json_encode($data['po_items']) }}">
    <button type="button" class="btn btn-success btn-block w-100" id="saveButton" disabled>
        Verify & Save PO
    </button>
</div>