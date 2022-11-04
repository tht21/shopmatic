@extends('dashboard.layouts.app')

@section('header-right')
    {{--<button type="button" class="btn btn-sm btn-neutral" {{ route('dashboard.accounts.create') }}>Add New</button>--}}
@endsection

@section('content')
    <create-account-component></create-account-component>
@endsection
