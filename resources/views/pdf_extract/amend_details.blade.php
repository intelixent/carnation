<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                Amend Purchase Order - #{{ $po_master->po_ref_num ?? '' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <form id="amendForm">
                @csrf
                <input type="hidden" name="po_id" value="{{ $po_master->id }}" />

                <div class="row mb-3">
                    <label for="job_order_id" class="col-form-label col-sm-2">Job Order:</label>
                    <div class="col-sm-10">
                        <select class="form-control select2-modal" id="job_order_id" name="job_order_id" required>
                            <option value="">Select Job Order</option>
                            @if($job_orders->count() > 0)
                            @foreach($job_orders as $job_order)
                            <option value="{{ $job_order->id }}">{{ $job_order->job_no }}
                                @if($job_order->type)
                                - {{ $job_order->type }}
                                @endif
                            </option>
                            @endforeach
                            @else
                            <option value="" disabled>No job orders available for this vendor</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="remarks" class="col-form-label col-sm-2">Remarks:</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" required></textarea>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" form="amendForm" class="btn btn-primary"
                @if($job_orders->count() == 0) disabled @endif>
                Save Changes
            </button>
        </div>
    </div>
</div>