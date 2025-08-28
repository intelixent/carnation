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
                                    <strong>P.O No:</strong> {{ $data['po_number'] ?? 'Not available' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>P.O Date:</strong> {{ $data['po_date'] ?? 'Not available' }}
                                </li>
                                <li class="list-group-item">
                                    <strong>Vendor Number:</strong> {{ $data['vendor_number'] ?? 'Not available' }}
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h5>Ship To</h5>
                            <ul class="list-group mb-3">
                                @if(isset($data['bill_to_ship_address']) && is_array($data['bill_to_ship_address']))
                                <li class="list-group-item">
                                    <strong>Address:</strong><br>
                                    @foreach($data['bill_to_ship_address'] as $addressLine)
                                    {{ $addressLine }}<br>
                                    @endforeach
                                </li>
                                @else
                                <li class="list-group-item">
                                    <strong>Address:</strong> {{ $data['bill_to_ship_address'] ?? 'Not available' }}
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
                                @if(isset($data['po_items']) && is_array($data['po_items']))
                                    @foreach($data['po_items'] as $index => $item)
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
                                        <td>{{ $item['Mrp'] ?? '' }}</td>
                                        <td>{{ $item['Stor e Loc'] ?? '' }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="19" class="text-center">No PO items found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{-- Material Breakdown Table --}}
                    <div class="mt-4">
                        <h5>Material Breakdown ({{ isset($data['material_descriptions']) ? count($data['material_descriptions']) : 0 }} items)</h5>
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
                                    @if(isset($data['material_descriptions']) && is_array($data['material_descriptions']))
                                        @foreach($data['material_descriptions'] as $index => $material)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $material['Material'] ?? '' }}</td>
                                            <td>{{ $material['Material description'] ?? '' }}</td>
                                            <td>{{ $material['Colour'] ?? '' }}</td>
                                            <td>{{ $material['Warer Trail'] ?? '' }}</td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center">No material descriptions found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
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
    <input type="hidden" name="vendor_name" id="vendor_name" value="Aditiya">
    <button type="button" class="btn btn-success btn-block w-100" id="saveButton" disabled>
        Verify & Save PO
    </button>
</div>