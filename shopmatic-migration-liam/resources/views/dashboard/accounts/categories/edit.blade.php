@extends('dashboard.layouts.app')

@section('header-right') @endsection

@section('content')
    <account-category-form-component mode='edit' :account_category="{{ $accountCategory }}"></account-category-form-component>
@endsection
