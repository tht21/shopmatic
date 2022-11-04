@extends('dashboard.layouts.app')

@section('header-right')

@endsection

@section('content')
    <create-user-management-component :shop_users="{{ $shopUsers }}" :is_create="true"></create-user-management-component>
@endsection
