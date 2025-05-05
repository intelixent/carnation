<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Vendor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="VendorEditForm">
                <input type="hidden" name="vendor_id" value="{{ $vendor_details['id'] }}">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="name" name="name" value="{{ $vendor_details['name'] }}" placeholder="Name">
                            <label for="name">Name</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="mobile" name="mobile" value="{{ $vendor_details['mobile'] }}" placeholder="Mobile">
                            <label for="mobile">Mobile</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email"
                                value="{{ $vendor_details['email'] }}" placeholder="Email">
                            <label for="email">Email</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <textarea class="form-control" id="address" name="address" placeholder="Address" style="height: 100px">{{ $vendor_details['address'] }}</textarea>
                            <label for="address">Address</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="gst_no" name="gst_no" value="{{ $vendor_details['gst_no'] }}" placeholder="GST No">
                            <label for="gst_no">GST No</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label for="state_id">State</label>
                        <select class="form-select select2" id="state_id" name="state_id">
                            <option value="">Choose State</option>
                            @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ $vendor_details['state_id'] == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6 ">
                        <div class="form-floating">
                            <textarea class="form-control" id="notes" name="notes" placeholder="Notes" style="height: 100px">{{ $vendor_details['notes'] }}</textarea>
                            <label for="notes">Notes</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="update_btn" name="update_btn"
                            style="float:right">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>