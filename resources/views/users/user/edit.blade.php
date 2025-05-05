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
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header justify-content-between bg-primary">
                    <div class="card-title text-white">
                        Edit User
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user_update', $user->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">{{ __('First Name') }}</label>
                                    <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror"
                                        name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                    @error('first_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">{{ __('Last Name') }}</label>
                                    <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror"
                                        name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                    @error('last_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Email and Mobile Number -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">{{ __('E-Mail Address') }}</label>
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                        name="email" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mobile">{{ __('Mobile') }}</label>
                                    <input id="mobile" type="text" class="form-control @error('mobile') is-invalid @enderror"
                                        name="mobile" value="{{ old('mobile', $user->mobile) }}" required>
                                    @error('mobile')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address">{{ __('Address') }}</label>
                                    <textarea id="address" class="form-control @error('address') is-invalid @enderror" name="address" required>{{ old('address', $user->address) }}</textarea>
                                    @error('address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">{{ __('Username') }}</label>
                                    <input id="username" type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username', $user->username) }}" required autocomplete="username">
                                    @error('username')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Role and Permissions -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role">{{ __('Role') }}</label>
                                    <select id="role" name="role" class="form-control select2" required>
                                        <option value="">Select a role</option>
                                        @foreach($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ old('role', $user->roles->first()->id ?? '') == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="permissions-container">
                            <h4>Permissions</h4>
                            <div id="permissions-list">
                                @foreach($permissions as $category => $perms)
                                <h5>{{ $category }}</h5>
                                <div class="row">
                                    @foreach($perms as $permission)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                id="permission_{{ $permission->id }}"
                                                {{ in_array($permission->id, old('permissions', $user->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Update User') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: function() {
                return $(this).data('placeholder');
            }
        });

        $('#role').change(function() {
            var roleId = $(this).val();
            if (roleId) {
                $.ajax({
                    url: "{{ route('get_role_permissions', '') }}/" + roleId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        var html = '';
                        $.each(data, function(category, permissions) {
                            html += '<h5>' + category + '</h5>';
                            html += '<div class="row">';
                            $.each(permissions, function(index, permission) {
                                html += '<div class="col-md-4">';
                                html += '<div class="form-check">';
                                html += '<input class="form-check-input" type="checkbox" name="permissions[]" value="' + permission.id + '" id="permission_' + permission.id + '" checked>';
                                html += '<label class="form-check-label" for="permission_' + permission.id + '">' + permission.name + '</label>';
                                html += '</div>';
                                html += '</div>';
                            });
                            html += '</div>';
                        });
                        $('#permissions-list').html(html);
                        $('#permissions-container').show();
                    },
                    error: function() {
                        alert('Error fetching permissions. Please try again.');
                    }
                });
            } else {
                $('#permissions-container').hide();
            }
        });

        $('#username').on('input', function() {
            $(this).val($(this).val().toLowerCase());
        });

        $('#mobile').on('keyup', function() {
            var mobile = $(this).val();
            var mobileRegex = /^[6-9]\d{9}$/;

            if (mobile != '') {
                if (!mobileRegex.test(mobile)) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Please enter a valid 10 digit mobile number starting with 6-9</div>');
                    }
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            }
        });

        $('#first_name, #last_name').on('keyup', function() {
            var name = $(this).val();
            var nameRegex = /^[A-Za-z]+(?: [A-Za-z]+)*$/;

            if (name != '') {
                if (!nameRegex.test(name)) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Please enter valid name (only letters and single spaces between words)</div>');
                    }
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            }
        });
    });
</script>
@endpush