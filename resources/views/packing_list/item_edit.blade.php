<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Packing List Item - # {{ $job_num }} | {{ $color }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="editItemForm">
                <input type="hidden" id="itemId" value="{{ $item->id }}">
                <input type="hidden" id="po_id" value="{{ $poId }}">
                <input type="hidden" id="color" value="{{ $color }}">
                <input type="hidden" id="carton_id" value="{{ $carton_id }}">

                <div class="row">
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
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="sizeSelect">Size</label>
                            <select class="form-control" id="sizeSelect" required>
                                <option value="">Select Size</option>
                                @foreach($sizesWithQty as $sizeData)
                                <option value="{{ $sizeData['size'] }}"
                                    data-max-qty="{{ $sizeData['remaining_qty'] + ($sizeData['size'] == $item->size ? $item->quantity : 0) }}"
                                    data-config-id="{{ $sizeData['config_item_id'] }}"
                                    {{ $sizeData['size'] == $item->size ? 'selected' : '' }}>
                                    {{ $sizeData['size'] }} (Available: {{ $sizeData['remaining_qty'] - $item->quantity  }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
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