<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add Carton Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="po_id" value="{{ $poId }}">
            <div class="mb-3">
                <label class="form-label">Carton</label>
                <select class="form-control" id="cartonSelect" required>
                    <option value="">Select Carton</option>
                    @foreach($cartons as $carton)
                    <option value="{{ $carton->id }}">{{ $carton->name }}</option>
                    @endforeach
                </select>
            </div>
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
                <input type="number" class="form-control" id="quantityInput" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="saveItemBtn">Save Item</button>
        </div>
    </div>
</div>