<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header text-dark">
            <h5 class="modal-title">
                <i class="fas fa-history me-2"></i>
                Invoice History - #{{ $invoice->ref_no }}
            </h5>
            <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-clock me-2"></i>Status History
                    </h6>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="30%">Date & Time</th>
                                    <th width="30%">Status</th>
                                    <th width="40%">Changed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $item)
                                <tr>
                                    <td>
                                        {{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y h:i A') }}
                                    </td>
                                    <td>
                                        @php
                                        $statusName = $item->invoiceStatus->name ?? 'Unknown';
                                        $status = strtolower(trim($statusName));

                                        $badgeClass = match($status) {
                                        'invoiced' => 'bg-info text-white',
                                        'in transit' => 'bg-warning text-dark',
                                        'grn pending' => 'bg-warning text-dark',
                                        'grn done' => 'bg-primary text-white',
                                        'payment pending' => 'bg-orange text-white',
                                        'payment received' => 'bg-success text-white',
                                        'invoice not disposed' => 'bg-secondary text-white',
                                        'cancelled' => 'bg-danger text-white',
                                        default => 'bg-light text-dark'
                                        };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $statusName }}</span>
                                    </td>
                                    <td>
                                        {{ $item->createdBy?->full_name ?? 'System' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No history found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>