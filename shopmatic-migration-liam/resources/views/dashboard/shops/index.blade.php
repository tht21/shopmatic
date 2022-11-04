@extends('dashboard.layouts.app')

@section('header-right')
    <create-shop-composite-component :global="global" @update="updateGlobal"></create-shop-composite-component>
@endsection

@section('content')
    <shop-management-component :global="global" :auth_user="{{ auth()->user() }}" :current_shop="{{session('shop')}}"></shop-management-component>
@endsection