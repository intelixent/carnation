<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add Packing List Item - # {{ $job_num }} | {{ $color }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="po_id" value="{{ $poId }}">
            <input type="hidden" id="color" value="{{ $color }}">
            <input type="hidden" id="carton_id" value="{{ $carton_id }}">

            <div class="mb-3">
                <label class="form-label">Article Number</label>
                <select class="form-control" id="articleSelect" required>
                    <option value="">Select Article</option>
                    @foreach($articles as $article)
                    <option value="{{ $article }}">{{ $article }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Size</label>
                <select class="form-control" id="sizeSelect" required disabled>
                    <option value="">Select Size</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control" id="quantityInput" min="1" required disabled>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="saveItemBtn">Save Item</button>
        </div>
    </div>
</div>