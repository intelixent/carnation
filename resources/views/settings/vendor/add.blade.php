<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add Vendor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="VendorAddForm">
                <div class="row  mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating ">
                            <input type="text" class="form-control form-control-sm" id="name" name="name" placeholder="Name">
                            <label for="name text-danger">Name</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating ">
                            <input type="text" class="form-control form-control-sm" id="location" name="mobile" placeholder="mobile">
                            <label for="mobile text-danger">Mobile</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating ">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                            <label for="email">Email</label>
                        </div>
                    </div>
                    <div class="col-sm-6 ">
                        <div class="form-floating">
                            <textarea class="form-control" id="address" name="address" placeholder="Address" style="height: 100px"></textarea>
                            <label for="address">Address</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating ">
                            <input type="text" class="form-control" id="gst_no" name="gst_no" placeholder="GST No">
                            <label for="gst_no">GST No</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label for="state_id">State</label>
                        <select class="form-control select2" id="state_id" name="state_id">
                            <option value="">Choose State</option>
                            @foreach($states as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6 ">
                        <div class="form-floating">
                            <textarea class="form-control" id="notes" name="notes" placeholder="Notes" style="height: 100px"></textarea>
                            <label for="notes">Notes</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="submit_btn" name="submit_btn" style="float:right">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>