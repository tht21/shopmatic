@extends('dashboard.layouts.app')

@section('header-right')
    <a href="{{ route('dashboard.inventory.composite.index') }}" class="btn btn-sm btn-neutral">Composite Inventory</a>
@endsection

@section('content')
    <inventory-index-component></inventory-index-component>
@endsection
