<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Vendor Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
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
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Legal Name</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->legal_name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Mobile</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->mobile ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Email</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->email ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">State</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->state->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Pincode</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->pincode ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Address Line 1</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->address_1 ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Address Line 2</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->address_2 ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">City/Town/Village</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->city_town_village ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">GST NO</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->gst_no ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">PAN NO</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->pan_no ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">GST Type</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->gst_type ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td colspan="2">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Place Supply</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->place_supply ?? 'N/A' }}</span>
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
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Payment Terms</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->payment_terms ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td colspan="2">
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
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>