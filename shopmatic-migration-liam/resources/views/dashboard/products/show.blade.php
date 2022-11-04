@extends('dashboard.layouts.app')

@section('header-right')
    <a class="btn btn-sm btn-neutral" href="{{ route('dashboard.products.edit', $product) }}"><i class="far fa-edit"></i> Edit</a>
@endsection

@section('content')
    <product-details-component :product="{{ json_encode($product) }}" slug="{{ $product->slug }}"></product-details-component>
@endsection
