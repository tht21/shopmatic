@extends('admin.layouts.app')

@section('header-right')
    
@endsection

@section('content')
    <admin-user-detail-component :user="{{ json_encode($user) }}"></admin-user-detail-component>
@endsection