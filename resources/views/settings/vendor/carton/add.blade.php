<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add Carton</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="CartonAddForm">
                @csrf
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <label for="vendor_id" class="form-label">Select Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 d-flex align-items-end">
                        <button type="button" class="btn btn-success" id="add-row-btn">
                            <i class="fas fa-plus"></i> Add Row
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="carton-table">
                        <thead class="table-primary">
                            <tr>
                                <th>Length <span class="text-danger">*</span></th>
                                <th>Breadth <span class="text-danger">*</span></th>
                                <th>Height <span class="text-danger">*</span></th>
                                <th>Weight <span class="text-danger">*</span></th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="carton-tbody">
                            <!-- Dynamic rows will be added here -->
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="submit_btn" name="submit_btn" style="float:right">
                            Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>