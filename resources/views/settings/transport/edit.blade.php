<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Transport</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="TransportEditForm">
                <input type="hidden" name="transport_id" value="{{ $transport_details['id'] }}">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-sm" id="name" name="name" value="{{ $transport_details['name'] }}" placeholder="Name">
                            <label for="name">Name</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="description" name="description" placeholder="Description" style="height: 100px">{{ $transport_details['description'] }}</textarea>
                            <label for="description">Description</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="update_btn" name="update_btn"
                            style="float:right">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>