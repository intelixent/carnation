<div class="modal-dialog modal-lg">
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
                <select class="form-control select2m" id="articleSelect" required>
                    <option value="">Select Article</option>
                    @foreach($articles as $article)
                    <option value="{{ $article }}">{{ $article }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3" id="sizesTableContainer" style="display: none;">
                <label class="form-label">Select Sizes and Quantities</label>
                <!-- Sizes table will be loaded here -->
            </div>

             <div class="mb-3">
                <label class="form-label">Net Weight</label>
                 <input type="text" class="form-control" id="net_weight">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="saveItemBtn">Save Items</button>
        </div>
    </div>
</div>