<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add Size Chart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="SizeChartForm">
                @csrf
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <label for="vendor_id" class="form-label">Select Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6" id="type_row" style="display: none;">
                        <label for="type">Type <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-control select2">
                            <option value="">Select Type</option>
                            <option value="Junior">Junior</option>
                            <option value="Men">Men</option>
                        </select>
                    </div>
                </div>

                <!-- Existing Sizes Display -->
                <div id="existing_sizes_container" style="display: none;" class="mb-3">
                    <div class="alert alert-info">
                        <strong>Existing Sizes:</strong>
                        @foreach($existingSizes as $key => $sizes)
                            <div class="existing-size-group" id="existing-sizes-{{ str_replace(['-', ' '], '_', $key) }}" style="display: none;">
                                @foreach($sizes as $size)
                                    <span class="badge bg-secondary me-1">{{ $size->size }}</span>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-12">
                        <label class="form-label">Sizes <span class="text-danger">*</span></label>
                        <div id="sizes_container">
                            <div class="input-group mb-2 size-row">
                                <input type="text" class="form-control" name="sizes[]" placeholder="Enter size" required>
                                <button type="button" class="btn btn-danger remove-size-row">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" id="add_size_row" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> Add More Size
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="submit_btn" style="float:right">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>