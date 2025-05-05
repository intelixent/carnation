@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Access Denied') }}</div>

                <div class="card-body">
                    <div class="alert alert-danger">
                        {{ $exception->getMessage() ?: 'You do not have permission to access this page.' }}
                    </div>
                    <a href="{{ route('home') }}" class="btn btn-primary">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 