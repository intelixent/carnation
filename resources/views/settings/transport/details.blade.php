<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Transport Details</h5>
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
                                    <span class="fw-semibold text-dark">{{ $transport_details->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Description</span>
                                    <span class="fw-semibold text-dark">{{ $transport_details->description ?? 'N/A' }}</span>
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