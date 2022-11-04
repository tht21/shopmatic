@extends('dashboard.layouts.app')

@section('header-right')
    <product-edit-header-component :product="{{json_encode($product)}}"></product-edit-header-component>
@endsection

@section('content')
    <edit-product-component :product="{{ json_encode($product) }}"></edit-product-component>
@endsection
