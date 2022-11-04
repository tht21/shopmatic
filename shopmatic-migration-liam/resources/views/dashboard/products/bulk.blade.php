@extends('dashboard.layouts.app')

{{--@section('header-right')
    <a class="btn btn-sm btn-neutral" href="{{ route('dashboard.products.import.tasks') }}">Past Imports</a>
@endsection--}}

@section('content')
    <bulk-product-component></bulk-product-component>
@endsection
