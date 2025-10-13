<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Size Chart Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-sm-12">
                    <div class="d-flex flex-column gap-1">
                        <span class="text-uppercase text-secondary" style="font-size: 11px;">Vendor</span>
                        <span class="fw-semibold text-dark">{{ $vendor->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            @if($type && $type != 'null')
            <div class="row mb-3">
                <div class="col-sm-12">
                    <div class="d-flex flex-column gap-1">
                        <span class="text-uppercase text-secondary" style="font-size: 11px;">Type</span>
                        <span class="fw-semibold text-dark">{{ $type }}</span>
                    </div>
                </div>
            </div>
            @endif
            <div class="row">
                <div class="col-sm-12">
                    <h6 class="mb-3">Sizes:</h6>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 80px;">S.No</th>
                                <th>Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($size_chart_details) && $size_chart_details->isNotEmpty())
                            @foreach($size_chart_details as $size)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="badge bg-primary">{{ $size->size }}</span></td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan="2" class="text-center text-muted">No sizes found</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>