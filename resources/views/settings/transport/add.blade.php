<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add Transport</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="TransportAddForm">
                <div class="row  mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating ">
                            <input type="text" class="form-control form-control-sm" id="name" name="name" placeholder="Name">
                            <label for="name text-danger">Name</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-12">
                        <div class="form-floating ">
                            <textarea class="form-control" id="description" name="description" placeholder="Description" style="height: 100px"></textarea>
                            <label for="description">Description</label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="submit_btn" name="submit_btn" style="float:right">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>