@extends('dashboard.layouts.app')

@section('header-right')
    <a href="{{ route('dashboard.products.create') }}" class="btn btn-sm btn-neutral">Add New</a>
@endsection

@section('content')
    <product-alert-index-component product_id="{{ request()->get('product_id') }}"></product-alert-index-component>
@endsection
