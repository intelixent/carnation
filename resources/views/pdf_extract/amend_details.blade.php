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
                    <label for="job_number" class="col-form-label col-sm-2">Job Number:</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="job_number" name="job_number" required />
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
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Close</button>
            <button type="submit" form="amendForm" class="btn btn-primary"> Save Changes</button>
        </div>
    </div>
</div>