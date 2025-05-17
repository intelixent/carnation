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
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePoDetails" aria-expanded="true" aria-controls="collapsePoDetails">
                                PO Details
                            </button>
                        </h2>
                        <div id="collapsePoDetails" class="accordion-collapse collapse show" aria-labelledby="headingPoDetails" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <!-- Section 1: General PO Details -->
                                    @php
                                    $po = $data['po_details'];
                                    @endphp
                                    <div class="col-md-6">
                                        <h5>PO Information</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item"><strong>PO Number:</strong> {{ $po['PO Number'] ?? '' }} - <strong>PO Date:</strong> {{ $po['PO Date'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Goods Ready Date:</strong> {{ $po['Goods Ready Date'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>MRP:</strong> {{ $po['MRP'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>VCP:</strong> {{ $po['VCP'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Colors:</strong> {{ $po['Colors'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>GSTIN:</strong> {{ $po['GSTIN'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>CIN:</strong> {{ $po['CIN'] ?? '' }}</li>
                                        </ul>
                                    </div>

                                    <!-- Section 2: Addresses -->
                                    <div class="col-md-6">
                                        <h5>Address Information</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item"><strong>Delivery Address:</strong> {{ $po['Delivery Address'] ?? '' }}</li>
                                            <li class="list-group-item"><strong>Communication Address:</strong> {{ $po['Communication Address'] ?? '' }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ARTICLE INFO --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingArticleInfo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseArticleInfo" aria-expanded="false" aria-controls="collapseArticleInfo">
                                Article Info
                            </button>
                        </h2>
                        <div id="collapseArticleInfo" class="accordion-collapse collapse" aria-labelledby="headingArticleInfo" data-bs-parent="#pdfAccordion">
                            <div class="accordion-body">
                                @php
                                $article_info = $data['article_info'];
                                @endphp
                                <table class="table table-bordered" style="width:100%">
                                    <tbody>
                                        <tr>
                                            <td><strong>ARTICLE:</strong>{{$article_info['ARTICLE']}}</td>
                                            <td colspan=4><strong>Article description:</strong><br />{{$article_info['Article description']}}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customs code:</strong>{{$article_info['Customs code']}}</td>
                                            <td><strong>Fabric composition:</strong><br />{{$article_info['Fabric composition']}}</td>
                                            <td><strong>Construction type:</strong><br />{{$article_info['Construction type']}}</td>
                                            <td><strong>Gender:</strong>{{$article_info['Gender']}}</td>
                                            <td><strong>Article group:</strong><br />{{$article_info['Article group']}}</td>
                                        </tr>

                                        <tr>
                                            <td><strong>Price per unit:</strong>{{$article_info['Price per unit']}}</td>
                                            <td><strong>Total unit:</strong><br />{{$article_info['Total unit']}}</td>
                                            <td><strong>Net Value:</strong><br />{{$article_info['Net Value']}}</td>
                                            <td><strong>Currency:</strong>{{$article_info['Currency']}}</td>
                                            <td><strong>Country of origin:</strong><br />{{$article_info['Country of origin']}}</td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>

                    {{-- PO ITEMS TABLE --}}
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
                                                @foreach(array_keys($data['po_items'][0]) as $header)
                                                <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                            $total_qty=0;
                                            $total_igst_value=0;

                                            preg_match('/\d+(\.\d+)?/', $article_info['Price per unit'], $matchess);
                                            $per_unit_price = $matchess[0];
                                            @endphp
                                            @foreach($data['po_items'] as $item)
                                            <?php

                                            preg_match('/\d+/', $item['quatity_uom'], $matches);
                                            $number = $matches[0];
                                            $total_qty += $number;

                                            $igst_taxable_value = floatval(str_replace(',', '', $item['igst_taxable_value']));
                                            $total_igst_value += ($igst_taxable_value * $number);

                                            ?>
                                            <tr>
                                                @foreach($item as $value)
                                                @php
                                                @endphp
                                                <td>{{ $value }}</td>
                                                @endforeach
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <?php
                                            $total = ($per_unit_price * $total_qty);
                                            $taxRate = 0.05; // 5%
                                            $taxAmount = $total * $taxRate;
                                            $finalTotal = $total + $taxAmount;
                                            ?>
                                            <tr>
                                                <td colspan=7>Total Value ( <?php echo $per_unit_price; ?> / EA)</td>
                                                <td colspan=2><?php echo $total; ?></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan=7>Total IGST</td>
                                                <td colspan=2><?php echo $taxAmount; ?></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan=7>Total Value Inc Tax</td>
                                                <td colspan=2><?php echo $finalTotal; ?></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan=7>Total Quantity</td>
                                                <td colspan=2><?php echo $total_qty; ?></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
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