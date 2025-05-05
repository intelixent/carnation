@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
<div class="container-fluid">
    <!-- BreadCrumbs -->
    <!-- <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">@yield('pagetitle')</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_main_title}}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{$page_title}}</li>
                </ol>
            </nav>
        </div>
    </div> -->
    <!-- BreadCrumbs -->

    <!-- row -->
    <div class="row mt-2">
        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif

        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Edit Role
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('role_update', $role->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">Role Name</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                name="name" value="{{ old('name', $role->name) }}" required autofocus>
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Permissions</label>
                            <hr />
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="select-all-permissions">
                                <label class="form-check-label" for="select-all-permissions">
                                    Select All Permissions
                                </label>
                            </div>
                            <div class="row">
                                @foreach ($permissions as $category => $categoryPermissions)
                                <div class="col-md-6 mb-3">
                                    <div class="panel panel-color" data-category="{{ $loop->index }}">
                                        <div class="panel-heading bd-gray-600 p-2">
                                            <div class="form-check">
                                                <input class="form-check-input select-category" type="checkbox" 
                                                    id="category_{{ $loop->index }}">
                                                <label class="form-check-label" for="category_{{ $loop->index }}">
                                                    <h5>{{ ucfirst($category) }}</h5>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="panel-body p-2">
                                            <div class="row">
                                                @foreach ($categoryPermissions as $permission)
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" 
                                                            type="checkbox" 
                                                            name="permissions[]" 
                                                            value="{{ $permission->id }}" 
                                                            id="permission_{{ $permission->id }}"
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @error('permissions')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Update Role
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- row closed -->
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('select-all-permissions');
        const categoryCards = document.querySelectorAll('.panel[data-category]');

        // Initial state check
        updateCategoryCheckboxes();
        updateSelectAllCheckbox();

        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            categoryCards.forEach(card => {
                const categoryCheckbox = card.querySelector('.select-category');
                const permissionCheckboxes = card.querySelectorAll('.permission-checkbox');
                categoryCheckbox.checked = isChecked;
                permissionCheckboxes.forEach(checkbox => checkbox.checked = isChecked);
            });
        });

        categoryCards.forEach(card => {
            const categoryCheckbox = card.querySelector('.select-category');
            const permissionCheckboxes = card.querySelectorAll('.permission-checkbox');

            categoryCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                permissionCheckboxes.forEach(checkbox => checkbox.checked = isChecked);
                updateSelectAllCheckbox();
            });

            permissionCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateCategoryCheckbox(card);
                    updateSelectAllCheckbox();
                });
            });
        });

        function updateCategoryCheckbox(card) {
            const categoryCheckbox = card.querySelector('.select-category');
            const permissionCheckboxes = card.querySelectorAll('.permission-checkbox');
            categoryCheckbox.checked = Array.from(permissionCheckboxes).every(checkbox => checkbox.checked);
        }

        function updateCategoryCheckboxes() {
            categoryCards.forEach(card => {
                updateCategoryCheckbox(card);
            });
        }

        function updateSelectAllCheckbox() {
            selectAllCheckbox.checked = Array.from(categoryCards).every(card => {
                const permissionCheckboxes = card.querySelectorAll('.permission-checkbox');
                return Array.from(permissionCheckboxes).every(checkbox => checkbox.checked);
            });
        }
    });
</script>
@endpush