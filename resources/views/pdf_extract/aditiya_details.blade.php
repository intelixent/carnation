<div class="modal-dialog modal-xl">
    <div class="modal-content">
        @php
        $po = $data['po_master'];
        // Reconstruct data from database
        $viewData = [
        'po_number' => $po->po_num,
        'po_date' => $po->po_date,
        'vendor_number' => json_decode($po->article_info, true)['vendor_number'] ?? '',
        'bill_to_ship_address' => explode(', ', $po->vendor_del_adr ?? ''),
        'po_items' => [],
        'material_descriptions' => [],
        ];

        // Reconstruct po_items and material_descriptions from po_items table
        foreach ($data['po_items'] as $index => $item) {
        $viewData['po_items'][] = [
        'Material Code' => $item->article_number,
        'HSN Number' => $item->hsn_code,
        'Qty' => $item->qty,
        'Unit' => $item->uom,
        'Per' => '1',
        'Rate/Unit' => $item->material_value,
        'Net Value' => $item->igst_taxable_value,
        'IGST %' => $item->igst_per,
        'CGST %' => '',
        'SGST %' => '',
        'UGST %' => '',
        'Val1' => $item->total_amount,
        'Val2' => '',
        'Delivery Date' => $item->due_date,
        'Size' => $item->size,
        'Sizewise Qty' => $item->qty,
        'MRP' => $item->mrp,
        'Stor e Loc' => $item->location,
        ];

        $viewData['material_descriptions'][] = [
        'Material' => $item->content,
        'Material description' => $item->style_description,
        'Colour' => $item->color,
        'Warer Trail' => $item->product_character,
        ];
        }
        @endphp

        <div class="modal-header">
            <h5 class="modal-title">Purchase Order Details - #{{ $po->po_ref_num ?? '' }}</h5>
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
                                    <div class="col-md-4">
                                        <h5>PO Information</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item">
                                                <strong>P.O No:</strong> {{ $viewData['po_number'] ?? 'Not available' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>P.O Date:</strong> {{ $viewData['po_date'] ?? 'Not available' }}
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Vendor Number:</strong> {{ $viewData['vendor_number'] ?? 'Not available' }}
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-4">
                                        <h5>Bill To</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item">
                                                <strong>Address:</strong><br>
                                                @if(is_array($viewData['bill_to_address']))
                                                @foreach($viewData['bill_to_address'] as $addressLine)
                                                {{ $addressLine }}<br>
                                                @endforeach
                                                @else
                                                {{ $viewData['bill_to_address'] ?? 'Not available' }}
                                                @endif
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-4">
                                        <h5>Ship To</h5>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item">
                                                <strong>Address:</strong><br>
                                                @if(is_array($viewData['ship_to_address']))
                                                @foreach($viewData['ship_to_address'] as $addressLine)
                                                {{ $addressLine }}<br>
                                                @endforeach
                                                @else
                                                {{ $viewData['ship_to_address'] ?? 'Not available' }}
                                                @endif
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
                                {{-- PO Items Table --}}
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>S.No</th>
                                                <th>Material Code</th>
                                                <th>HSN Number</th>
                                                <th>Qty</th>
                                                <th>Unit</th>
                                                <th>Per</th>
                                                <th>Rate/Unit</th>
                                                <th>Net Value</th>
                                                <th>IGST %</th>
                                                <th>CGST %</th>
                                                <th>SGST %</th>
                                                <th>UGST %</th>
                                                <th>Val 1</th>
                                                <th>Val 2</th>
                                                <th>Delivery Date</th>
                                                <th>Size</th>
                                                <th>Sizewise Qty</th>
                                                <th>MRP</th>
                                                <th>Store Loc</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($viewData['po_items'] as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $item['Material Code'] ?? '' }}</td>
                                                <td>{{ $item['HSN Number'] ?? '' }}</td>
                                                <td>{{ $item['Qty'] ?? '' }}</td>
                                                <td>{{ $item['Unit'] ?? '' }}</td>
                                                <td>{{ $item['Per'] ?? '' }}</td>
                                                <td>{{ $item['Rate/Unit'] ?? '' }}</td>
                                                <td>{{ $item['Net Value'] ?? '' }}</td>
                                                <td>{{ $item['IGST %'] ?? '' }}</td>
                                                <td>{{ $item['CGST %'] ?? '' }}</td>
                                                <td>{{ $item['SGST %'] ?? '' }}</td>
                                                <td>{{ $item['UGST %'] ?? '' }}</td>
                                                <td>{{ $item['Val1'] ?? '' }}</td>
                                                <td>{{ $item['Val2'] ?? '' }}</td>
                                                <td>{{ $item['Delivery Date'] ?? '' }}</td>
                                                <td>{{ $item['Size'] ?? '' }}</td>
                                                <td>{{ $item['Sizewise Qty'] ?? '' }}</td>
                                                <td>{{ $item['MRP'] ?? '' }}</td>
                                                <td>{{ $item['Stor e Loc'] ?? '' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Material Breakdown Table --}}
                                <div class="mt-4">
                                    <h5>Material Breakdown ({{ count($viewData['material_descriptions']) }} items)</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Material</th>
                                                    <th>Material Description</th>
                                                    <th>Colour</th>
                                                    <th>Warer Trail</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($viewData['material_descriptions'] as $index => $material)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $material['Material'] ?? '' }}</td>
                                                    <td>{{ $material['Material description'] ?? '' }}</td>
                                                    <td>{{ $material['Colour'] ?? '' }}</td>
                                                    <td>{{ $material['Warer Trail'] ?? '' }}</td>
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
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>