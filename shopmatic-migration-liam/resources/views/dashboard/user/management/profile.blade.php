
@extends('dashboard.layouts.app')

@section('header-right')
@endsection

@section('content')
    <profile-component :auth_user="{{ auth()->user() }}"></profile-component>
@endsection