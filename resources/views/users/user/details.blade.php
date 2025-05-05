<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">User Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <nav class="nav nav-style-6 nav-pills mb-3 nav-justified d-sm-flex d-block" role="tablist">
                <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#nav-user-justified" aria-selected="true">User Details</a>
                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#nav-permissions-justified" aria-selected="false">Role Permissions</a>
            </nav>

            <div class="tab-content">
                <!-- User Details Tab -->
                <div class="tab-pane show active text-muted" id="nav-user-justified" role="tabpanel">
                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-bordered" style="width:100%">
                                <tr>
                                    <td colspan="2">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">Full Name</span>
                                            <span class="fw-semibold text-dark">{{ $user->first_name . ' ' . $user->last_name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">Username</span>
                                            <span class="fw-semibold text-dark">{{ $user->username ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">Email</span>
                                            <span class="fw-semibold text-dark">{{ $user->email ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">Mobile</span>
                                            <span class="fw-semibold text-dark">{{ $user->mobile ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">Designation</span>
                                            <span class="fw-semibold text-dark">{{ $user->designation ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">Address</span>
                                            <span class="fw-semibold text-dark">{{ $user->address ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">Role</span>
                                            <span class="fw-semibold text-dark">{{ $user->roles->first()->name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Permissions Tab -->
                <div class="tab-pane text-muted" id="nav-permissions-justified" role="tabpanel">
                    <div class="row">
                        <div class="col-sm-12">
                            @php
                                $directPermissions = $user->permissions->pluck('id')->toArray();
                                $rolePermissions = $user->roles->first()->permissions->groupBy('category');
                            @endphp

                            @foreach ($rolePermissions as $category => $permissions)
                                <div class="mb-4">
                                    <h6 class="text-uppercase mb-3">{{ $category }}</h6>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Permission Name</th>
                                                <th width="120">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($permissions as $permission)
                                                <tr>
                                                    <td>{{ $permission->name }}</td>
                                                    <td>
                                                        @if (in_array($permission->id, $directPermissions))
                                                            <span class="badge bg-success">Granted</span>
                                                        @else
                                                            <span class="badge bg-danger">Not Granted</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>