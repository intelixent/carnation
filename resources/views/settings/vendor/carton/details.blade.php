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
                            <td colspan="2">
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
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Email</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->email ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td colspan="2">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Address</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->address ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">GST NO</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->gst_no ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">State</span>
                                    <span class="fw-semibold text-dark">{{ $vendor_details->state->name ?? 'N/A' }}</span>
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
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>