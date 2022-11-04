@extends('dashboard.layouts.app')

@section('header-right') @endsection

@section('content')
    <account-category-form-component mode='create' :account_category="null"></account-category-form-component>
@endsection
