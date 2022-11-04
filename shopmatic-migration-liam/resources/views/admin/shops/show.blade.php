@extends('admin.layouts.app')

@section('header-right')

@endsection

@section('content')
    <admin-shop-details-component :shop="{{$shop}}"></admin-shop-details-component>
@endsection
