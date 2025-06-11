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
                                                <strong>Delivery Date:</strong> {{ $po->goods_ready_date ?? '' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Delivery Date:</strong> {{ $po->season ?? '' }}
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-6">
                                        <h5>Delivery Address</h5>
                                        <ul class="list-group mb-3">
                                            @if(isset($po->vendor_del_adr) && is_array(json_decode($po->vendor_del_adr, true)))
                                            <li class="list-group-item">
                                                <strong>Address:</strong><br>
                                                @foreach(json_decode($po->vendor_del_adr, true) as $addressLine)
                                                {{ $addressLine }}<br>
                                                @endforeach
                                            </li>
                                            @else
                                            <li class="list-group-item">
                                                <strong>Address:</strong> {{ $po->vendor_del_adr ?? 'Not available' }}
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

                                {{-- Detailed PO Items Table --}}
                                <div class="mt-4">
                                    <h5>Detailed Items</h5>
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
                                                $totalMaterialValue = 0;
                                                $totalGstAmount = 0;
                                                $totalValue = 0;
                                                @endphp

                                                @foreach($data['po_items'] as $item)
                                                @php
                                                $qty = floatval($item->qty ?? 0);
                                                $unitPrice = floatval(str_replace(',', '', $item->unit_price ?? 0));
                                                $materialValue = $qty * $unitPrice;
                                                $gstAmount = floatval(str_replace(',', '', $item->igst_taxable_value ?? 0));
                                                $lineTotal = floatval(str_replace(',', '', $item->total_value ?? 0));

                                                $totalMaterialValue += $materialValue;
                                                $totalGstAmount += $gstAmount;
                                                $totalValue += $lineTotal;
                                                @endphp
                                                <tr>
                                                    <td>{{ $item->sno }}</td>
                                                    <td>{{ $item->hsn_code }}</td>
                                                    <td>{{ $item->article_number }}</td>
                                                    <td>{{ $item->part_description }}</td>
                                                    <td><strong>{{ $item->id_color }}</strong></td>
                                                    <td>{{ $item->size_grp }}</td>
                                                    <td><strong>{{ number_format($qty) }}</strong></td>
                                                    <td>{{ number_format($unitPrice, 2) }}</td>
                                                    <td>{{ number_format($materialValue, 2) }}</td>
                                                    <td>{{ $item->igst_per }}%</td>
                                                    <td>{{ number_format($gstAmount, 2) }}</td>
                                                    <td>{{ number_format($lineTotal, 2) }}</td>
                                                    <td>{{ $item->due_date }}</td>
                                                    <td>{{ number_format(floatval(str_replace(',', '', $item->mrp ?? 0)), 2) }}</td>
                                                </tr>
                                                @endforeach

                                                {{-- Grand Total Row --}}
                                                <tr class="table-secondary">
                                                    <td colspan="8" class="text-end"><strong>Grand Total</strong></td>
                                                    <td><strong>{{ number_format($totalMaterialValue, 2) }}</strong></td>
                                                    <td></td>
                                                    <td><strong>{{ number_format($totalGstAmount, 2) }}</strong></td>
                                                    <td><strong>{{ number_format($totalValue, 2) }}</strong></td>
                                                    <td colspan="2"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {{-- Size Breakdown Table --}}
                                @if(!empty($data['size_breakdown']['data']))
                                <div class="mt-4">
                                    <h5>Size Breakdown</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Color</th>
                                                    @foreach($data['size_breakdown']['sizes'] as $size)
                                                    <th>{{ $size }}</th>
                                                    @endforeach
                                                    <th>TOTAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data['size_breakdown']['data'] as $row)
                                                <tr>
                                                    <td><strong>{{ $row['Color'] }}</strong></td>
                                                    @foreach($data['size_breakdown']['sizes'] as $size)
                                                    <td>{{ $row[$size] }}</td>
                                                    @endforeach
                                                    <td><strong>{{ number_format($row['TOTAL']) }}</strong></td>
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