@extends('dashboard.layouts.app')

@section('header-right')
    <create-inventory-composite-component :global="global" @update="updateGlobal"></create-inventory-composite-component>
@endsection

@section('content')
    <inventory-composite-component :global="global"></inventory-composite-component>
@endsection
