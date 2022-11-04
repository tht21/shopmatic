@extends('dashboard.layouts.app')

@section('header-right')
    
@endsection

@section('head')
<script src="https://js.stripe.com/v3/"></script>
@endsection

@section('content')
    <create-billing-component :intent="{{ json_encode($intent) }}"></create-billing-component>
@endsection
