<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Packing List Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="editItemForm">
                <input type="hidden" id="itemId" value="{{ $item->id }}">
                <input type="hidden" id="po_id" value="{{ $poId }}">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="cartonSelect">Carton Name</label>
                            <select class="form-control" id="cartonSelect" required>
                                <option value="">Select Carton</option>
                                @foreach($cartons as $carton)
                                    <option value="{{ $carton->id }}" {{ $carton->id == $item->carton_id ? 'selected' : '' }}>
                                        {{ $carton->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="articleSelect">Article Number</label>
                            <select class="form-control" id="articleSelect" required>
                                <option value="">Select Article</option>
                                @foreach($articles as $article)
                                    <option value="{{ $article }}" {{ $article == $item->article_number ? 'selected' : '' }}>
                                        {{ $article }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="sizeSelect">Size</label>
                            <select class="form-control" id="sizeSelect" required>
                                <option value="">Select Size</option>
                                @foreach($sizes as $size)
                                    <option value="{{ $size }}" {{ $size == $item->size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="quantityInput">Quantity</label>
                            <input type="number" class="form-control" id="quantityInput" min="1" value="{{ $item->quantity }}" required>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveItemBtn">Update Item</button>
        </div>
    </div>
</div>