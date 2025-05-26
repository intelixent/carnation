<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Carton</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="CartonEditForm">
                @csrf
                <input type="hidden" name="id" value="{{ $carton->id }}">

                <div class="row mb-3">
                    <div class="col-sm-12">
                        <label for="vendor_name" class="form-label">Vendor</label>
                        <input type="text" id="vendor_name" class="form-control"
                            value="{{ $carton->vendor->name ?? 'Unknown Vendor' }}"
                            readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                        <small class="text-muted">Vendor cannot be changed</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-12">
                        <label for="edit_name" class="form-label">Carton Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control"
                            value="{{ $carton->name }}" placeholder="Enter carton name" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <label for="edit_length" class="form-label">Length <span class="text-danger">*</span></label>
                        <input type="number" name="length" id="edit_length" class="form-control"
                            value="{{ $carton->length }}" placeholder="Enter length" step="0.01" required>
                    </div>
                    <div class="col-sm-6">
                        <label for="edit_breadth" class="form-label">Breadth <span class="text-danger">*</span></label>
                        <input type="number" name="breadth" id="edit_breadth" class="form-control"
                            value="{{ $carton->breadth }}" placeholder="Enter breadth" step="0.01" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <label for="edit_height" class="form-label">Height <span class="text-danger">*</span></label>
                        <input type="number" name="height" id="edit_height" class="form-control"
                            value="{{ $carton->height }}" placeholder="Enter height" step="0.01" required>
                    </div>
                    <div class="col-sm-6">
                        <label for="edit_weight" class="form-label">Weight <span class="text-danger">*</span></label>
                        <input type="number" name="weight" id="edit_weight" class="form-control"
                            value="{{ $carton->weight }}" placeholder="Enter weight" step="0.01" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12 text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button class="btn btn-primary" type="submit" id="update_btn" name="update_btn">
                            Update Carton
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>