@extends('dashboard.layouts.app')

@section('header-right')
    <export-task-component :global="global" url="{{ route('dashboard.products.export.tasks') }}"></export-task-component>
    {{--<a class="btn btn-sm btn-neutral" href="{{ route('dashboard.products.export.tasks') }}">Past Exports</a>--}}

@endsection

@section('content')
    <export-product-index-component :global="global" @update="updateGlobal"></export-product-index-component>
@endsection
