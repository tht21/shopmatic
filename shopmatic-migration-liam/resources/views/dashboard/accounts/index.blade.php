@extends('dashboard.layouts.app')

@section('header-right')
    <a class="btn btn-sm btn-neutral" href="{{ route('dashboard.accounts.create') }}">Add New</a>
@endsection

@section('content')
    <account-component :request_url="'{{ route('web.accounts.index') }}'"></account-component>
@endsection