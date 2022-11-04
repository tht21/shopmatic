@extends('dashboard.layouts.app')

@section('header-right')

@endsection

@section('content')
    <create-user-management-component :shop_users="{{ $user }}"></create-user-management-component>
@endsection
