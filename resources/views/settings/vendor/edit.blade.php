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
                            <input type="text" class="form-control form-control-sm" id="name" name="name" value="{{ $vendor_details['name'] }}" placeholder="Name" readonly>
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
                            <input type="email" class="form-control" id="email" name="email" value="{{ $vendor_details['email'] }}" placeholder="Email">
                            <label for="email">Email</label>
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
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <textarea class="form-control" id="address_1" name="address_1" placeholder="Address Line 1" style="height: 100px">{{ $vendor_details['address_1'] }}</textarea>
                            <label for="address_1">Address Line 1</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <textarea class="form-control" id="address_2" name="address_2" placeholder="Address Line 2" style="height: 100px">{{ $vendor_details['address_2'] }}</textarea>
                            <label for="address_2">Address Line 2</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="city_town_village" name="city_town_village" value="{{ $vendor_details['city_town_village'] }}" placeholder="City/Town/Village">
                            <label for="city_town_village">City/Town/Village</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="pincode" name="pincode" value="{{ $vendor_details['pincode'] }}" placeholder="Pincode">
                            <label for="pincode">Pincode</label>
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
                        <div class="form-floating">
                            <input type="text" class="form-control" id="pan_no" name="pan_no" value="{{ $vendor_details['pan_no'] }}" placeholder="Pan No">
                            <label for="pan_no">Pan No</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="gst_type" name="gst_type" value="{{ $vendor_details['gst_type'] }}" placeholder="GST Type">
                            <label for="gst_type">GST Type</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="place_supply" name="place_supply" value="{{ $vendor_details['place_supply'] }}" placeholder="Place Supply">
                            <label for="place_supply">Place Supply</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="excess" name="excess" value="{{ $vendor_details['excess'] }}" placeholder="Excess">
                            <label for="excess">Excess %</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="shortage" name="shortage" value="{{ $vendor_details['shortage'] }}" placeholder="Shortage">
                            <label for="shortage">Shortage %</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="discount" name="discount" value="{{ $vendor_details['discount'] }}" placeholder="Discount %">
                            <label for="discount">Discount %</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="payment_terms" name="payment_terms" value="{{ $vendor_details['payment_terms'] }}" placeholder="Payment Terms">
                            <label for="payment_terms">Payment Terms</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <textarea class="form-control" id="notes" name="notes" placeholder="Notes" style="height: 100px">{{ $vendor_details['notes'] }}</textarea>
                            <label for="notes">Notes</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="legal_name" name="legal_name" value="{{ $vendor_details['legal_name'] }}" placeholder="Legal Name">
                            <label for="legal_name">Legal Name</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="update_btn" name="update_btn" style="float:right">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>