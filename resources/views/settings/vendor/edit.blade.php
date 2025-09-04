<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Vendor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="VendorEditForm">
                <input type="hidden" name="vendor_id" value="{{ $vendor_details['id'] }}">

                <!-- Tabs -->
                <nav class="nav nav-pills nav-fill mb-4" role="tablist">
                    <a class="nav-link active rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-basic" aria-selected="true">
                        <i class="fas fa-user me-2"></i>Basic Info
                    </a>
                    <a class="nav-link rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-billing" aria-selected="false">
                        <i class="fas fa-file-invoice me-2"></i>Billing Address
                    </a>
                    <a class="nav-link rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-shipping" aria-selected="false">
                        <i class="fas fa-shipping-fast me-2"></i>Shipping Address
                    </a>
                </nav>

                <div class="tab-content">
                    <!-- Basic Info Tab -->
                    <div class="tab-pane show active" id="nav-basic" role="tabpanel">
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
                                <div class="form-floating">
                                    <textarea class="form-control" id="notes" name="notes" placeholder="Notes" style="height: 80px">{{ $vendor_details['notes'] }}</textarea>
                                    <label for="notes">Notes</label>
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
                    </div>

                    <!-- Billing Address Tab -->
                    <div class="tab-pane" id="nav-billing" role="tabpanel">
                        <h6 class="mb-3 text-primary">Billing Address</h6>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control form-control-sm" id="billing_legal_name" name="billing_legal_name" value="{{ $vendor_details['billing_legal_name'] }}" placeholder="Legal Name">
                                    <label for="billing_legal_name">Legal Name</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="billing_state_id">State</label>
                                <select class="form-select select2" id="billing_state_id" name="billing_state_id">
                                    <option value="">Choose State</option>
                                    @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ $vendor_details['billing_state_id'] == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="billing_address_1" name="billing_address_1" placeholder="Address Line 1" style="height: 80px">{{ $vendor_details['billing_address_1'] }}</textarea>
                                    <label for="billing_address_1">Address Line 1</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="billing_address_2" name="billing_address_2" placeholder="Address Line 2" style="height: 80px">{{ $vendor_details['billing_address_2'] }}</textarea>
                                    <label for="billing_address_2">Address Line 2</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="billing_city_town_village" name="billing_city_town_village" value="{{ $vendor_details['billing_city_town_village'] }}" placeholder="City/Town/Village">
                                    <label for="billing_city_town_village">City/Town/Village</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="billing_pincode" name="billing_pincode" value="{{ $vendor_details['billing_pincode'] }}" placeholder="Pincode">
                                    <label for="billing_pincode">Pincode</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="billing_gst_no" name="billing_gst_no" value="{{ $vendor_details['billing_gst_no'] }}" placeholder="GST No">
                                    <label for="billing_gst_no">GST No</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="billing_pan_no" name="billing_pan_no" value="{{ $vendor_details['billing_pan_no'] }}" placeholder="Pan No">
                                    <label for="billing_pan_no">Pan No</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control form-control-sm" id="billing_gst_type" name="billing_gst_type" value="{{ $vendor_details['billing_gst_type'] }}" placeholder="GST Type">
                                    <label for="billing_gst_type">GST Type</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address Tab -->
                    <div class="tab-pane" id="nav-shipping" role="tabpanel">
                        <h6 class="mb-3 text-success">Shipping Address</h6>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control form-control-sm" id="shipping_legal_name" name="shipping_legal_name" value="{{ $vendor_details['shipping_legal_name'] }}" placeholder="Legal Name">
                                    <label for="shipping_legal_name">Legal Name</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="shipping_state_id">State</label>
                                <select class="form-select select2" id="shipping_state_id" name="shipping_state_id">
                                    <option value="">Choose State</option>
                                    @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ $vendor_details['shipping_state_id'] == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="shipping_address_1" name="shipping_address_1" placeholder="Address Line 1" style="height: 80px">{{ $vendor_details['shipping_address_1'] }}</textarea>
                                    <label for="shipping_address_1">Address Line 1</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="shipping_address_2" name="shipping_address_2" placeholder="Address Line 2" style="height: 80px">{{ $vendor_details['shipping_address_2'] }}</textarea>
                                    <label for="shipping_address_2">Address Line 2</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="shipping_city_town_village" name="shipping_city_town_village" value="{{ $vendor_details['shipping_city_town_village'] }}" placeholder="City/Town/Village">
                                    <label for="shipping_city_town_village">City/Town/Village</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="shipping_pincode" name="shipping_pincode" value="{{ $vendor_details['shipping_pincode'] }}" placeholder="Pincode">
                                    <label for="shipping_pincode">Pincode</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="shipping_gst_no" name="shipping_gst_no" value="{{ $vendor_details['shipping_gst_no'] }}" placeholder="GST No">
                                    <label for="shipping_gst_no">GST No</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="shipping_pan_no" name="shipping_pan_no" value="{{ $vendor_details['shipping_pan_no'] }}" placeholder="Pan No">
                                    <label for="shipping_pan_no">Pan No</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control form-control-sm" id="shipping_place_supply" name="shipping_place_supply" value="{{ $vendor_details['shipping_place_supply'] }}" placeholder="Place Supply">
                                    <label for="shipping_place_supply">Place Supply</label>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control form-control-sm" id="shipping_distance" name="shipping_distance" value="{{ $vendor_details['shipping_distance'] }}" placeholder="Distance">
                                    <label for="shipping_distance">Distance</label>
                                </div>
                            </div>
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