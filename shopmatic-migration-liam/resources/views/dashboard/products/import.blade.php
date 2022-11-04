@extends('dashboard.layouts.app')

@section('header-right')
    <a class="btn btn-sm btn-neutral" href="{{ route('dashboard.products.import.tasks') }}">Past Imports</a>
@endsection

@section('content')
    <import-product-component></import-product-component>
@endsection