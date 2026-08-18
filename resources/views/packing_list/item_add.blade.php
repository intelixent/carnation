<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                Add Packing List Item - # {{ $job_num }} | {{ $color }}
                @if($vendorId == 2 && isset($articleNumber) && isset($country))
                | {{ $articleNumber }} | {{ $country }}
                @elseif(isset($location))
                | {{ $location }}
                @endif
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="po_id" value="{{ $poId }}">
            <input type="hidden" id="color" value="{{ $color }}">
            <input type="hidden" id="location" value="{{ $location ?? '' }}">
            <input type="hidden" id="carton_id" value="{{ $carton_id }}">
            <input type="hidden" id="article_number" value="{{ $articleNumber ?? '' }}">
            <input type="hidden" id="country" value="{{ $country ?? '' }}">

            <!-- Packing Table Number Selection -->
            <div class="mb-4 border p-3 rounded bg-light">
                <label class="form-label fw-bold">Packing Table Number <span class="text-danger">*</span></label>
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="packing_table_no" id="table1" value="1"
                            {{ !$isFirstTime && $existingPackingTableNo == 1 ? 'checked' : '' }}
                            {{ !$isFirstTime ? 'disabled' : '' }} required>
                        <label class="form-check-label" for="table1">
                            Table 1
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="packing_table_no" id="table2" value="2"
                            {{ !$isFirstTime && $existingPackingTableNo == 2 ? 'checked' : '' }}
                            {{ !$isFirstTime ? 'disabled' : '' }} required>
                        <label class="form-check-label" for="table2">
                            Table 2
                        </label>
                    </div>
                </div>
                @if(!$isFirstTime)
                <small class="text-muted">Table number is already set and cannot be changed.</small>
                @else
                <small class="text-muted">Please select a packing table number before proceeding.</small>
                @endif
            </div>

            @if($vendorId == 2)
            <!-- For Vendor 2: Show sizes directly without article select -->
            <div class="mb-3 sizesTableContainer">
                <label class="form-label">Select Sizes and Quantities</label>
                <!-- Sizes will be loaded automatically via JavaScript -->
            </div>
            @else
            <!-- For other vendors: Show article selection -->

            {{-- Bulk carton generation - Super Admin / Manager only, vendors 1/3/5/6/7.
                 $showCartonCountInput is resolved server-side in item_add(); this field
                 simply doesn't render for anyone/anything else. --}}
            @if(!empty($showCartonCountInput) && $showCartonCountInput)
            <div class="mb-3 border p-3 rounded bg-light" id="cartonCountWrapper">
                <label class="form-label fw-bold">No. of Cartons</label>
                <input type="number" min="1" class="form-control" id="cartonCount" placeholder="Enter number of cartons to generate">
                <small class="text-muted">
                    Leave blank (or 1) to add a single carton. Enter a number to generate that
                    many identical cartons using the sizes/quantities selected below - if any
                    size doesn't have enough remaining quantity for that many cartons, you'll
                    get a warning before it saves.
                </small>
            </div>
            @endif

            <div id="articlesWrapper">
                <div class="article-block mb-4 border p-3 rounded">
                    <button type="button" class="btn-close float-end remove-article" title="Remove this article"></button>
                    <div class="mb-3">
                        <div class="input-group">
                            <label class="form-label">Article Number</label>
                            <select class="form-control select2m articleSelect" required style="width:100%">
                                <option value="">Select Article</option>
                                @foreach($articles as $article)
                                <option value="{{ $article }}">{{ $article }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-underline text-primary" id="addArticleBtn">+ Add Another Article</button>
                        </div>
                    </div>

                    <div class="mb-3 sizesTableContainer">
                        <label class="form-label">Select Sizes and Quantities</label>
                        <!-- Sizes table will be loaded here -->
                    </div>
                </div>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Net Weight</label>
                <input type="text" class="form-control" id="net_weight" class="net_weight">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="saveItemBtn">Save Items</button>
        </div>
    </div>
</div>