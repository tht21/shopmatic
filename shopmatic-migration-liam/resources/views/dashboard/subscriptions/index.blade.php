@extends('dashboard.layouts.app')

@section('header-right')
    
@endsection

@section('head')

@endsection

@section('content')
    <subscription-index-component :subscription="{{ json_encode($subscription) }}"></subscription-index-component>
@endsection
