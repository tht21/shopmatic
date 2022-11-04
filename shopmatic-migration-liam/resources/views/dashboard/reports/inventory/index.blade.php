@extends('dashboard.layouts.app')

@section('header-right')
    <export-inventory-component :global="global"></export-inventory-component>
@endsection

@section('content')
    <index-inventory-component :global="global" @update="updateGlobal"></index-inventory-component>
@endsection
