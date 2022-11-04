@extends('dashboard.layouts.app')

@section('header-right')
    <a href="{{ route('dashboard.products.bulk.categories') }}" class="btn btn-sm btn-neutral">Edit Category</a>
    <a href="{{ route('dashboard.products.create') }}" class="btn btn-sm btn-neutral">Add New</a>
@endsection

@section('content')
    <product-index-component></product-index-component>
@endsection