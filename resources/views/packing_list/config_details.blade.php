<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3">
                <p class="mb-1"><strong>Job Number:</strong> <span id="job_num">{{ $po->po_job_num }}</span></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1"><strong>Style Ref:</strong> <span id="style_ref">{{ $styleRef ?? '' }}</span></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1"><strong>PO Number:</strong> <span id="po_num">{{ $po->po_num }}</span></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1"><strong>Excess:</strong> <span id="excess">{{ $po->vendor->excess }} %</span></p>
            </div>
        </div>
    </div>
</div>

@if($hasPackingListItems)
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i>
                <strong>Thank you!</strong> Packing list items have already been created for this PO. Configuration cannot be modified.
            </div>
        </div>
    </div>
@endif

<form id="packingConfigForm">
    @csrf
    <input type="hidden" name="po_id" value="{{ $po->id }}">

    <div class="row mt-3">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="150px"></th>
                            <th width="120px">COLOR</th>
                            @foreach($allSizes as $size)
                            <th class="text-center">{{ $size }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($colorSizeMatrix as $color => $sizes)
                        <!-- PO QTY Row for this color -->
                        <tr>
                            <td>PO QTY</td>
                            <td rowspan="2" class="text-center align-middle"><strong>{{ $color }}</strong></td>
                            @foreach($allSizes as $size)
                            <td class="text-center">
                                {{ isset($sizes[$size]) && $sizes[$size] > 0 ? $sizes[$size] : '-' }}
                            </td>
                            @endforeach
                        </tr>

                        <!-- PACK QTY Row for this color -->
                        <tr>
                            <td>PACK QTY</td>
                            @foreach($allSizes as $size)
                            <td class="text-center">
                                {{ isset($packQtyMatrix[$color][$size]) && $packQtyMatrix[$color][$size] > 0 ? $packQtyMatrix[$color][$size] : '-' }}
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td><strong>TOTAL</strong></td>
                            <td><strong>PACK QTY</strong></td>
                            @foreach($allSizes as $size)
                            <td class="text-center"><strong>{{ $packQtyBySizeTotal[$size] > 0 ? $packQtyBySizeTotal[$size] : '-' }}</strong></td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-4">
            <label for="carton_select" class="form-label"><strong>Select Carton:</strong></label>
            <select name="carton_id" id="carton_select" class="form-control select2" required>
                <option value="">Select Carton</option>
                @foreach($cartons as $carton)
                <option value="{{ $carton->id }}" {{ $selectedCartonId == $carton->id ? 'selected' : '' }}>
                    {{ $carton->length }} x {{ $carton->breadth }} x {{ $carton->height }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary" {{ $hasPackingListItems ? 'disabled' : '' }}>
                Save Configuration
            </button>
        </div>
    </div>
</form>