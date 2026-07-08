<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add Packing List Item - # {{ $job_num }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="dmart_po_id" value="{{ $poId }}">
            <input type="hidden" id="dmart_carton_id" value="{{ $carton_id }}">

            {{-- Packing Table Number Selection --}}
            <div class="mb-4 border p-3 rounded bg-light">
                <label class="form-label fw-bold">Packing Table Number <span class="text-danger">*</span></label>
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="dmart_packing_table_no" id="dmart_table1" value="1"
                            {{ !$isFirstTime && $existingPackingTableNo == 1 ? 'checked' : '' }}
                            {{ !$isFirstTime ? 'disabled' : '' }} required>
                        <label class="form-check-label" for="dmart_table1">Table 1</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="dmart_packing_table_no" id="dmart_table2" value="2"
                            {{ !$isFirstTime && $existingPackingTableNo == 2 ? 'checked' : '' }}
                            {{ !$isFirstTime ? 'disabled' : '' }} required>
                        <label class="form-check-label" for="dmart_table2">Table 2</label>
                    </div>
                </div>
                @if(!$isFirstTime)
                <small class="text-muted">Table number is already set and cannot be changed.</small>
                @else
                <small class="text-muted">Please select a packing table number before proceeding.</small>
                @endif
            </div>

            {{-- Generate Carton / Remaining Cartons --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Generate Carton <span class="text-danger">*</span></label>
                    <input type="number" min="1" max="{{ $remainingCartons }}" class="form-control" id="generateCartonCount" placeholder="No. of cartons">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Remaining Cartons</label>
                    <input type="text" class="form-control" id="remainingCartonsDisplay" value="{{ $remainingCartons }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Total Cartons</label>
                    <input type="text" class="form-control" id="dmartTotalCartonsDisplay" value="{{ $totalCartons }}" readonly>
                </div>
            </div>

            <div class="table-responsive mb-1">
                <table class="table table-bordered table-sm align-middle" id="dmartQtyTable" data-case-lot="{{ $caseLot ?? 0 }}" data-total-qty="{{ $poTotalQty ?? 0 }}">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width:160px;">Color</th>
                            @foreach($sizes as $size)
                            <th class="text-center">{{ $size }}</th>
                            @endforeach
                            <th class="text-center">Row Total</th>
                        </tr>
                    </thead>
                    <tbody id="dmartQtyBody">
                        @forelse($colorSizeMatrix as $color => $sizeQtys)
                        <tr>
                            <td>
                                <input type="text" class="form-control form-control-sm" value="{{ $color }}" readonly>
                            </td>
                            @foreach($sizes as $size)
                            <td class="text-center">
                                <input type="number" min="0" class="form-control form-control-sm dmart-qty-input"
                                    data-color="{{ $color }}" data-size="{{ $size }}"
                                    value="{{ $sizeQtys[$size] ?? 0 }}">
                            </td>
                            @endforeach
                            <td class="text-center dmart-qty-row-total">{{ array_sum($sizeQtys) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ count($sizes) + 2 }}" class="text-center">No carton qty / size data found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <small class="text-muted d-block mb-3">These are the quantities packed per single carton. Editing them updates every carton you generate from here on.</small>

            <div class="mb-3">
                <label class="form-label">Net Weight</label>
                <input type="text" class="form-control" id="dmart_net_weight">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="saveDmartCartonsBtn">Generate Cartons</button>
        </div>
    </div>
</div>