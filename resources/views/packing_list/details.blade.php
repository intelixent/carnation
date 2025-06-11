<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Packing List Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <!-- PO Details -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <p class="mb-1"><strong>Job Number:</strong></p>
                    <p>{{ $packingList->po->po_job_num }}</p>
                </div>
                <div class="col-md-3">
                    <p class="mb-1"><strong>PO Number:</strong></p>
                    <p>{{ $packingList->po_no }}</p>
                </div>
                <div class="col-md-3">
                    <p class="mb-1"><strong>PO Date:</strong></p>
                    <p>{{ $packingList->po_date }}</p>
                </div>
                <div class="col-md-3">
                    <p class="mb-1"><strong>Vendor:</strong></p>
                    <p>{{ $packingList->vendor->name ?? $packingList->vendor_id }}</p>
                </div>
            </div>

            <hr>

            <!-- Items Table -->
            <h6 class="mb-3">Items in Packing List</h6>
            @if($packingList->items->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Carton Number</th>
                            <th>Article Number</th>
                            <th>Size</th>
                            <th>Quantity</th>
                            <th>Packed By</th>
                            <th>Packed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packingList->items as $item)
                        <tr>
                            <td>{{ $item->carton_name ?? 'N/A' }}</td>
                            <td>{{ $item->article_number }}</td>
                            <td>{{ $item->size }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->packed->full_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y H:i:s') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-muted text-center">No items found.</p>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>