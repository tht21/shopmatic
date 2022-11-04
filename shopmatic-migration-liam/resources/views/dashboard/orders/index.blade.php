@extends('dashboard.layouts.app')

{{--@section('header-right')--}}
{{--    <a href="{{ route('dashboard.orders.create') }}" class="btn btn-sm btn-neutral">Create Order</a>--}}
{{--@endsection--}}

@section('content')
    <order-index-component></order-index-component>
@endsection
