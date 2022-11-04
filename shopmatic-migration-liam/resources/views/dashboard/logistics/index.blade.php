@extends('dashboard.layouts.app')

@section('header-right')
    <a href="{{ route('dashboard.logistics.create') }}" class="btn btn-sm btn-neutral">Create Logistics</a>
@endsection

@section('content')
    <logistic-index-component></logistic-index-component>
@endsection
