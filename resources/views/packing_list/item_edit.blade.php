<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Packing List Item - # {{ $job_num }} | {{ $color }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="itemId" value="{{ $item->id }}">
            <input type="hidden" id="po_id" value="{{ $poId }}">
            <input type="hidden" id="color" value="{{ $color }}">
            <input type="hidden" id="carton_id" value="{{ $carton_id }}">
            <input type="hidden" id="originalSize" value="{{ $item->size }}">
            <input type="hidden" id="currentConfigId" value="{{ $configItemId ?? '' }}">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="articleSelect">Article Number</label>
                        <select class="form-control select2m" id="articleSelect" disabled>
                            <option value="{{ $item->article_number }}" selected>{{ $item->article_number }}</option>
                        </select>
                        <small class="text-muted">Article number cannot be changed during edit</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="sizeSelect">Size</label>
                        <select class="form-control" id="sizeSelect" required>
                            <option value="">Select Size</option>
                            <!-- Sizes will be populated dynamically -->
                        </select>
                        <small class="text-muted">Select a size to check availability</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="quantityInput">Quantity</label>
                        <input type="number"
                            class="form-control quantity-input"
                            id="quantityInput"
                            data-max-qty="0"
                            value="{{ $item->quantity }}"
                            min="1"
                            disabled
                            required>
                        <small class="text-muted" id="quantityHelp">
                            Select a size first
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveItemBtn" disabled>Update Item</button>
        </div>
    </div>
</div>