@extends('dashboard.layouts.app')

@section('content')
    <account-settings-component :account="{{ json_encode($account) }}"></account-settings-component>
@endsection
