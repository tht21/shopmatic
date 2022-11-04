@extends('admin.layouts.app')

@section('header-right')
    <button type="button" class="btn btn-sm btn-neutral" data-toggle="modal" data-target="#create-user-form">Create</button>
@endsection

@section('content')
    <admin-user-index-component></admin-user-index-component>
@endsection