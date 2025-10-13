<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Vendor Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
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
                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-bordered" style="width:100%">
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Name</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Mobile</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->mobile ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Email</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->email ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Excess</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->excess ?? 'N/A' }} %</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Shortage</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shortage ?? 'N/A' }} %</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Discount</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->discount ?? 'N/A' }} %</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Payment Terms</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->payment_terms ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Notes</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->notes ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Billing Address Tab -->
                <div class="tab-pane" id="nav-billing" role="tabpanel">
                    <h6 class="mb-3 text-primary">Billing Address Details</h6>
                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-bordered" style="width:100%">
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Legal Name</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billing_legal_name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">State</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billingState->name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Pincode</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billing_pincode ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Address Line 1</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billing_address_1 ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Address Line 2</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billing_address_2 ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">City/Town/Village</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billing_city_town_village ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">GST NO</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billing_gst_no ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">PAN NO</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billing_pan_no ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">GST Type</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->billing_gst_type ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address Tab -->
                <div class="tab-pane" id="nav-shipping" role="tabpanel">
                    <h6 class="mb-3 text-success">Shipping Address Details</h6>
                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-bordered" style="width:100%">
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Legal Name</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shipping_legal_name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">State</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shippingState->name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Pincode</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shipping_pincode ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Address Line 1</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shipping_address_1 ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Address Line 2</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shipping_address_2 ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">City/Town/Village</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shipping_city_town_village ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">GST NO</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shipping_gst_no ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">PAN NO</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shipping_pan_no ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">Place Supply</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->shipping_place_supply ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @if($vendor_details->id == 2)
                                <!-- UAE Shipping Address Fields -->
                                <tr>
                                    <td colspan="3">
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-globe me-2"></i>UAE Shipping Address
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">UAE Legal Name</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->uae_shipping_legal_name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">UAE Address Line 1</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->uae_shipping_address_1 ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">UAE Address Line 2</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->uae_shipping_address_2 ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">UAE City/Town/Village</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->uae_shipping_city_town_village ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td colspan="2">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px;">UAE Place Supply</span>
                                            <span class="fw-semibold text-dark">{{ $vendor_details->uae_shipping_place_supply ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>