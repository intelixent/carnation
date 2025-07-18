<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Update Invoice Details - #{{ $invoice->ref_no }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="InvoiceUpdateForm">
                <input type="hidden" name="id" value="{{ $invoice->id }}">
                <!-- Tabs -->
                <nav class="nav nav-pills nav-fill mb-4" role="tablist">
                    <a class="nav-link active rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-invoice" aria-selected="true">
                        <i class="fas fa-table me-2"></i>Invoice
                    </a>
                    <a class="nav-link rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-address" aria-selected="false">
                        <i class="fas fa-table me-2"></i>Address
                    </a>
                    <a class="nav-link rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-transport" aria-selected="false">
                        <i class="fas fa-table me-2"></i>Transport
                    </a>
                </nav>

                <div class="tab-content">
                    <!-- Invoice Tab -->
                    <div class="tab-pane show active" id="nav-invoice" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="invoice_no" name="invoice_no" placeholder="Invoice No" value="{{ $invoice['ref_no'] ?? '' }}" required>
                                    <label for="invoice_no">Invoice No</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="invoice_date" name="invoice_date" placeholder="Invoice Date" value="{{ $invoice['inv_date'] ?? '' }}" required>
                                    <label for="invoice_date">Invoice Date</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Tab -->
                    <div class="tab-pane" id="nav-address" role="tabpanel">
                        <div class="row">
                            <!-- Billed To Column -->
                            <div class="col-md-6">
                                <h6 class="mb-3 text-primary">Billed To</h6>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="billed_legal_name" name="billed_legal_name" value="{{ $billedToDetails['billed_legal_name'] ?? '' }}" placeholder="Legal Name" required>
                                            <label for="billed_legal_name">Legal Name</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="billed_address_1" name="billed_address_1" value="{{ $billedToDetails['billed_address_1'] ?? '' }}" placeholder="Address 1" required>
                                            <label for="billed_address_1">Address 1</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="billed_address_2" name="billed_address_2" value="{{ $billedToDetails['billed_address_2'] ?? '' }}" placeholder="Address 2" required>
                                            <label for="billed_address_2">Address 2</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="billed_city" name="billed_city" value="{{ $billedToDetails['billed_city'] ?? '' }}" placeholder="City/Town/Village" required>
                                            <label for="billed_city">City/Town/Village</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select class="form-select" id="billed_state" name="billed_state" required>
                                                <option value="">Select State</option>
                                                @foreach($states as $state)
                                                <option value="{{ $state->id }}" {{ ($billedToDetails['billed_state'] ?? '') == $state->id ? 'selected' : '' }}>{{ $state->name }} ({{ $state->code }})</option>
                                                @endforeach
                                            </select>
                                            <label for="billed_state">State</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="billed_gst_no" name="billed_gst_no" value="{{ $billedToDetails['billed_gst_no'] ?? '' }}" placeholder="GST No" required>
                                            <label for="billed_gst_no">GST No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="billed_pan_no" name="billed_pan_no" value="{{ $billedToDetails['billed_pan_no'] ?? '' }}" placeholder="PAN No" required>
                                            <label for="billed_pan_no">PAN No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="billed_pincode" name="billed_pincode" value="{{ $billedToDetails['billed_pincode'] ?? '' }}" placeholder="Pincode" required>
                                            <label for="billed_pincode">Pincode</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="billed_gst_type" name="billed_gst_type" value="{{ $billedToDetails['billed_gst_type'] ?? '' }}" placeholder="GST Type" required>
                                            <label for="billed_gst_type">GST Type</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipped To Column -->
                            <div class="col-md-6">
                                <h6 class="mb-3 text-success">Shipped To</h6>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="shipped_legal_name" name="shipped_legal_name" value="{{ $shippedToDetails['shipped_legal_name'] ?? '' }}" placeholder="Legal Name" required>
                                            <label for="shipped_legal_name">Legal Name</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="shipped_address_1" name="shipped_address_1" value="{{ $shippedToDetails['shipped_address_1'] ?? '' }}" placeholder="Address 1" required>
                                            <label for="shipped_address_1">Address 1</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="shipped_address_2" name="shipped_address_2" value="{{ $shippedToDetails['shipped_address_2'] ?? '' }}" placeholder="Address 2" required>
                                            <label for="shipped_address_2">Address 2</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="shipped_city" name="shipped_city" value="{{ $shippedToDetails['shipped_city'] ?? '' }}" placeholder="City/Town/Village" required>
                                            <label for="shipped_city">City/Town/Village</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select class="form-select" id="shipped_state" name="shipped_state" required>
                                                <option value="">Select State</option>
                                                @foreach($states as $state)
                                                <option value="{{ $state->id }}" {{ ($shippedToDetails['shipped_state'] ?? '') == $state->id ? 'selected' : '' }}>{{ $state->name }} ({{ $state->code }})</option>
                                                @endforeach
                                            </select>
                                            <label for="shipped_state">State</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="shipped_gst_no" name="shipped_gst_no" value="{{ $shippedToDetails['shipped_gst_no'] ?? '' }}" placeholder="GST No" required>
                                            <label for="shipped_gst_no">GST No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="shipped_pan_no" name="shipped_pan_no" value="{{ $shippedToDetails['shipped_pan_no'] ?? '' }}" placeholder="PAN No" required>
                                            <label for="shipped_pan_no">PAN No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="shipped_pincode" name="shipped_pincode" value="{{ $shippedToDetails['shipped_pincode'] ?? '' }}" placeholder="Pincode" required>
                                            <label for="shipped_pincode">Pincode</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="shipped_place_of_supply" name="shipped_place_of_supply" value="{{ $shippedToDetails['shipped_place_of_supply'] ?? '' }}" placeholder="Place of Supply" required>
                                            <label for="shipped_place_of_supply">Place of Supply</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transport Tab -->
                    <div class="tab-pane" id="nav-transport" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <select class="form-select" id="transport_name" name="transport_name" required>
                                        <option value="">Select Transport Name</option>
                                        @foreach($transports as $transport)
                                        <option value="{{ $transport->id }}" {{ ($transportDetails['transport_name'] ?? '') == $transport->id ? 'selected' : '' }}>{{ $transport->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="transport_name">Transport Name</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="mode_of_transport" name="mode_of_transport" placeholder="Mode of Transport" value="{{ $transportDetails['mode_of_transport'] ?? '' }}" required>
                                    <label for="mode_of_transport">Mode Of Transport</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="transport_date_time" name="transport_date_time" placeholder="Transport Supply Date and Time" value="{{ $transportDetails['transport_date_time'] ?? '' }}" required>
                                    <label for="transport_date_time">Transport Supply Date and Time</label>
                                </div>
                            </div>
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