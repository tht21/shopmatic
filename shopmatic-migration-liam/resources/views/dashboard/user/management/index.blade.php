@extends('dashboard.layouts.app')

@section('header-right')
    <a class="btn btn-sm btn-neutral" href="{{ route('dashboard.shop.users.create') }}">{{ __('field.add_new') }}</a>
@endsection

@section('content')
    <user-management-component :auth_user="{{ auth()->user() }}"></user-management-component>
@endsection
