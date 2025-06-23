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
                    <a class="nav-link active rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-address" aria-selected="true">
                        <i class="fas fa-table me-2"></i>Address
                    </a>
                    <a class="nav-link rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-irn" aria-selected="false">
                        <i class="fas fa-table me-2"></i>IRN
                    </a>
                    <a class="nav-link rounded-pill" data-bs-toggle="tab" role="tab" href="#nav-transport" aria-selected="false">
                        <i class="fas fa-table me-2"></i>Transport
                    </a>
                </nav>

                <div class="tab-content">
                    <!-- Address Tab -->
                    <div class="tab-pane show active" id="nav-address" role="tabpanel">
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

                    <!-- IRN Tab -->
                    <div class="tab-pane" id="nav-irn" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="irn_no" name="irn_no" value="{{ $irnDetails['irn_no'] ?? '' }}" placeholder="IRN No" required>
                                    <label for="irn_no">IRN No</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="acknowledgment_no" name="acknowledgment_no" value="{{ $irnDetails['acknowledgment_no'] ?? '' }}" placeholder="Acknowledgment No" required>
                                    <label for="acknowledgment_no">Acknowledgment No</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="document_no" name="document_no" value="{{ $irnDetails['document_no'] ?? '' }}" placeholder="Document No" required>
                                    <label for="document_no">Document No</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="supply_type_code" name="supply_type_code" value="{{ $irnDetails['supply_type_code'] ?? '' }}" placeholder="Supply Type Code" required>
                                    <label for="supply_type_code">Supply Type Code</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="eway_bill_no" name="eway_bill_no" value="{{ $irnDetails['eway_bill_no'] ?? '' }}" placeholder="E-WAY BILL NO" required>
                                    <label for="eway_bill_no">E-WAY BILL NO</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="eway_bill_date" name="eway_bill_date" value="{{ $irnDetails['eway_bill_date'] ?? '' }}" required>
                                    <label for="eway_bill_date">E-WAY BILL DATE</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="acknowledgment_date" name="acknowledgment_date" value="{{ $irnDetails['acknowledgment_date'] ?? '' }}" required>
                                    <label for="acknowledgment_date">Acknowledgment Date</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="document_date" name="document_date" value="{{ $irnDetails['document_date'] ?? '' }}" required>
                                    <label for="document_date">Document Date</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="reverse_charge" name="reverse_charge" placeholder="Reverse Charge" value="{{ $irnDetails['reverse_charge'] ?? '' }}" required>
                                    <label for="reverse_charge">Reverse Charge</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="preceeding_document_no" name="preceeding_document_no" value="{{ $irnDetails['preceeding_document_no'] ?? '' }}" placeholder="Preceeding Document No" required>
                                    <label for="preceeding_document_no">Preceeding Document No</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="preceeding_document_date" name="preceeding_document_date" value="{{ $irnDetails['preceeding_document_date'] ?? '' }}" required>
                                    <label for="preceeding_document_date">Preceeding Document Date</label>
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
                                    <input type="text" class="form-control" id="transport_vehicle_no" name="transport_vehicle_no" placeholder="Vehicle No" value="{{ $transportDetails['transport_vehicle_no'] ?? '' }}" required>
                                    <label for="transport_vehicle_no">Vehicle No</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="transport_distance" name="transport_distance" placeholder="Distance (KM)" value="{{ $transportDetails['transport_distance'] ?? '' }}" required>
                                    <label for="transport_distance">Distance (KM)</label>
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