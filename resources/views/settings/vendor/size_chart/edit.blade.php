<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Size Chart - {{ $vendor->name ?? 'N/A' }} @if($type && $type != 'null') ({{ $type }}) @endif</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="SizeChartEditForm">
                @csrf
                <input type="hidden" name="vendor_id" value="{{ $vendor->id ?? '' }}">
                @if($type && $type != 'null')
                <input type="hidden" name="type" value="{{ $type }}">
                @endif

                <!-- Existing Sizes -->
                @if(isset($existing_sizes) && $existing_sizes->isNotEmpty())
                <div class="mb-4">
                    <h6 class="mb-3">Existing Sizes:</h6>
                    <div id="existing_sizes_list">
                        @foreach($existing_sizes as $size)
                        <div class="input-group mb-2 existing-size-row">
                            <input type="hidden" name="updated_sizes[{{ $loop->index }}][id]" value="{{ $size->id }}">
                            <input type="text" class="form-control" name="updated_sizes[{{ $loop->index }}][size]" value="{{ $size->size }}" required>
                            <button type="button" class="btn btn-danger delete-existing-size" data-id="{{ $size->id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="alert alert-info mb-4">
                    No existing sizes found. Add new sizes below.
                </div>
                @endif

                <!-- Add New Sizes -->
                <div class="mb-3">
                    <h6 class="mb-3">Add New Sizes:</h6>
                    <div id="new_sizes_container"></div>
                    <button type="button" id="add_new_size_row" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Add New Size
                    </button>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="update_btn" style="float:right">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>