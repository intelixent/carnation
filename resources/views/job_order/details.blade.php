<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title text-dark">Job Order Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-12">
                    <!-- Job Order Information -->
                    <table class="table table-bordered" style="width:100%">
                        <tr>
                            <td style="width: 50%">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Job No</span>
                                    <span class="fw-semibold text-dark">{{ $job_order_details->job_no ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td style="width: 50%">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Vendor</span>
                                    <span class="fw-semibold text-dark">{{ $job_order_details->vendor->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Style</span>
                                    <span class="fw-semibold text-dark">{{ $job_order_details->style ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Color</span>
                                    <span class="fw-semibold text-dark">{{ $job_order_details->color ?? 'N/A' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <!-- Show Type only if vendor_id is 1 -->
                            @if($job_order_details->vendor_id == 1)
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Type</span>
                                    <span class="fw-semibold text-dark">{{ $job_order_details->type ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Created At</span>
                                    <span class="fw-semibold text-dark">
                                        {{ $job_order_details->created_at ? date('d-M-Y h:i A', strtotime($job_order_details->created_at)) : 'N/A' }}
                                    </span>
                                </div>
                            </td>
                            @else
                            <td colspan="2">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-uppercase text-secondary" style="font-size: 11px;">Created At</span>
                                    <span class="fw-semibold text-dark">
                                        {{ $job_order_details->created_at ? date('d-M-Y h:i A', strtotime($job_order_details->created_at)) : 'N/A' }}
                                    </span>
                                </div>
                            </td>
                            @endif
                        </tr>
                    </table>

                    <!-- Size Details - Horizontal Layout -->
                    @if($job_order_details->sizes && $job_order_details->sizes->count() > 0)
                    <div class="mt-4">
                        <h6 class="mb-3 fw-bold">Size Quantities</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        @foreach($job_order_details->sizes as $sizeDetail)
                                        <th style="text-align: center; vertical-align: middle; min-width: 100px;">
                                            {{ $sizeDetail->size->size ?? 'N/A' }}
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @php $totalQty = 0; @endphp
                                        @foreach($job_order_details->sizes as $sizeDetail)
                                        @php $totalQty += $sizeDetail->qty ?? 0; @endphp
                                        <td style="text-align: center; vertical-align: middle;" class="fw-semibold">
                                            {{ number_format($sizeDetail->qty ?? 0) }}
                                        </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <th colspan="{{ $job_order_details->sizes->count() }}" style="text-align: center; vertical-align: middle;">
                                            Total Quantity: {{ number_format($totalQty) }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info mt-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <span>No size details available for this job order.</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>