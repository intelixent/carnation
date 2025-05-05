<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><?php echo $role->name; ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-12">
                    <?php 
                    $allPermissions = $permissions->groupBy('category');
                    foreach($allPermissions as $category => $categoryPermissions): 
                    ?>
                    <div class="mb-4">
                        <h6 class="text-uppercase mb-3"><?php echo $category; ?></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Permission Name</th>
                                        <th width="120">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($categoryPermissions as $permission): ?>
                                    <tr>
                                        <td><?php echo $permission->name; ?></td>
                                        <td>
                                            <?php if($role->hasPermissionTo($permission->name)): ?>
                                                <span class="badge bg-success">Granted</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Granted</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>